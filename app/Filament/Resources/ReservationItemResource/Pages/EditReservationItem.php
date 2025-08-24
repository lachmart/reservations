<?php

namespace App\Filament\Resources\ReservationItemResource\Pages;

use App\Filament\Resources\ReservationItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditReservationItem extends EditRecord
{
    protected static string $resource = ReservationItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
