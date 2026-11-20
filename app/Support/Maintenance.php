<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Server housekeeping that otherwise needs SSH (php artisan …), runnable from
 * the web: on FTP-only hosting it's triggered by the deploy script through a
 * token-protected URL, or by the owner from the admin "Údržba" page.
 */
class Maintenance
{
    public const TASKS = [
        'deploy' => 'Po nahrání: migrace, cache, úložiště',
        'migrate' => 'Spustit migrace databáze',
        'optimize' => 'Obnovit cache (konfigurace, šablony)',
        'storage-link' => 'Propojit úložiště fotek (public/storage)',
    ];

    /**
     * Tasks only for the deploy script (not shown as admin buttons).
     */
    public const SCRIPT_TASKS = [
        'import-database' => 'Import storage/app/import.sql.gz into an EMPTY database (moving hosts)',
        'test-mail' => 'Send a test e-mail to the owner inbox (checks the SMTP settings)',
    ];

    /** Where the moving dump is uploaded; deleted after a successful import. */
    public static function importPath(): string
    {
        return storage_path('app/import.sql.gz');
    }

    /**
     * @return array{ok: bool, output: string}
     */
    public function run(string $task): array
    {
        $log = [];
        $ok = true;

        $steps = match ($task) {
            'deploy' => ['migrate', 'optimize', 'storage-link'],
            'migrate', 'optimize', 'storage-link', 'import-database', 'test-mail' => [$task],
            default => throw new \InvalidArgumentException("Unknown maintenance task [{$task}]."),
        };

        foreach ($steps as $step) {
            [$stepOk, $output] = $this->{'step'.str_replace('-', '', ucwords($step, '-'))}();
            $log[] = "== {$step}: ".($stepOk ? 'OK' : 'FAILED')."\n".trim($output);
            $ok = $ok && $stepOk;
            if (! $stepOk) {
                break; // don't cache config on top of a failed migration
            }
        }

        return ['ok' => $ok, 'output' => implode("\n\n", $log)];
    }

    /**
     * One-off import when moving hosts. Refuses to touch a database that already
     * has tables, so it can never overwrite live data.
     */
    private function stepImportDatabase(): array
    {
        $path = self::importPath();

        if (! is_file($path)) {
            return [false, 'No dump at storage/app/import.sql.gz.'];
        }

        if (Schema::getTableListing() !== []) {
            return [false, 'The database is not empty; refusing to import over existing tables.'];
        }

        $sql = gzdecode((string) file_get_contents($path));
        if ($sql === false || trim($sql) === '') {
            return [false, 'The dump could not be read (expected gzip SQL).'];
        }

        try {
            DB::unprepared($sql);
        } catch (Throwable $e) {
            report($e);

            return [false, 'Import failed: '.$e->getMessage()];
        }

        @unlink($path);

        return [true, 'Imported '.count(Schema::getTableListing()).' tables; the dump file was deleted.'];
    }

    private function stepTestMail(): array
    {
        $to = config('contact.owner_notify');

        try {
            Mail::raw(
                "Zkušební e-mail z webu ".config('app.url')." (".now()->format('d.m.Y H:i').").\n"
                ."Odesílání (".self::mailRoute().") funguje. Není potřeba odpovídat.",
                fn ($m) => $m->to($to)->subject('Maraka rental: zkušební e-mail'),
            );
        } catch (Throwable $e) {
            report($e);

            return [false, 'Sending failed: '.$e->getMessage()];
        }

        return [true, 'Sent to '.implode(', ', (array) $to).' via '.self::mailRoute().'.'];
    }

    private static function mailRoute(): string
    {
        $mailer = (string) config('mail.default');

        return $mailer === 'smtp' ? 'SMTP '.config('mail.mailers.smtp.host') : $mailer;
    }

    private function stepMigrate(): array
    {
        return $this->artisan('migrate', ['--force' => true]);
    }

    private function stepOptimize(): array
    {
        $out = [];
        foreach (['optimize:clear', 'config:cache', 'view:cache', 'filament:cache-components'] as $command) {
            [$ok, $output] = $this->artisan($command);
            $out[] = "{$command}: ".trim($output);
            if (! $ok) {
                return [false, implode("\n", $out)];
            }
        }

        return [true, implode("\n", $out)];
    }

    private function stepStorageLink(): array
    {
        $link = public_path('storage');

        if (config('filesystems.links') === []) {
            // PUBLIC_STORAGE_IN_PUBLIC: a plain folder instead of a link.
            if (! is_dir($link) && ! mkdir($link, 0775, true)) {
                return [false, 'Could not create public/storage.'];
            }

            return [true, 'public/storage is a plain folder (PUBLIC_STORAGE_IN_PUBLIC).'];
        }

        if (file_exists($link)) {
            return [true, is_link($link) ? 'public/storage is already linked.' : 'public/storage is a folder (PUBLIC_STORAGE_IN_PUBLIC), nothing to link.'];
        }

        if (! function_exists('symlink')) {
            return [false, 'The hosting disables symlink(). Set PUBLIC_STORAGE_IN_PUBLIC=true in .env and upload photos to public/storage.'];
        }

        [$ok, $output] = $this->artisan('storage:link');

        return file_exists($link)
            ? [true, $output]
            : [false, trim($output)."\nCould not create the link. Set PUBLIC_STORAGE_IN_PUBLIC=true in .env and upload photos to public/storage."];
    }

    /**
     * @return array{0: bool, 1: string}
     */
    private function artisan(string $command, array $params = []): array
    {
        try {
            $code = Artisan::call($command, $params);

            return [$code === 0, Artisan::output()];
        } catch (Throwable $e) {
            report($e);

            return [false, $e->getMessage()];
        }
    }
}
