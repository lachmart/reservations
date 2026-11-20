<?php

namespace App\Filament\Pages;

use App\Support\Maintenance as MaintenanceTasks;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Support\HtmlString;

/**
 * "Údržba": what `php artisan migrate / optimize / storage:link` used to do over
 * SSH, as buttons for hosting where there is no SSH.
 */
class Maintenance extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Údržba';

    protected static ?string $title = 'Údržba serveru';

    protected static ?int $navigationSort = 100;

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('K čemu to je')
                ->schema([
                    Text::make('Na hostingu bez SSH se tady spouští to, co se jinak dělá příkazem php artisan. Po nahrání nové verze přes FTP stačí „Po nahrání“ (deploy skript to dělá sám).'),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return collect(MaintenanceTasks::TASKS)
            ->map(fn (string $label, string $task) => Action::make($task)
                ->label($label)
                ->color($task === 'deploy' ? 'primary' : 'gray')
                ->requiresConfirmation()
                ->action(function () use ($task, $label) {
                    $result = app(MaintenanceTasks::class)->run($task);

                    Notification::make()
                        ->title($label.($result['ok'] ? ': hotovo' : ': chyba'))
                        ->body(new HtmlString('<pre style="white-space:pre-wrap;font-size:11px">'.e(mb_strimwidth($result['output'], 0, 2000, '…')).'</pre>'))
                        ->{$result['ok'] ? 'success' : 'danger'}()
                        ->persistent()
                        ->send();
                }))
            ->values()
            ->all();
    }
}
