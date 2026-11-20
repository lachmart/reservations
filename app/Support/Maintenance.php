<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
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
     * @return array{ok: bool, output: string}
     */
    public function run(string $task): array
    {
        $log = [];
        $ok = true;

        $steps = match ($task) {
            'deploy' => ['migrate', 'optimize', 'storage-link'],
            'migrate', 'optimize', 'storage-link' => [$task],
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
