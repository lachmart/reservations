<?php

namespace App\Filament\Resources\ReservationItemResource\Pages;

use App\Filament\Resources\ReservationItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListReservationItems extends ListRecords
{
    protected static string $resource = ReservationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
