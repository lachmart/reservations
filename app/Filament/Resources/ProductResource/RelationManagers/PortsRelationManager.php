<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use Filament\Schemas\Schema;
use App\Models\Port;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Tables;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Actions\AttachAction;
use Filament\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;

class PortsRelationManager extends RelationManager
{
    protected static string $relationship = 'ports';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('pivot.amount') // Keep using pivot.amount for display
            ->numeric()
                ->label('Amount')
                ->required(),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Port Name'),
                TextColumn::make('gender')->label('Gender'),
                TextColumn::make('pivot.amount')->label('Amount'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->form([
                        Select::make('port_id')
                            ->label('Select Port')
                            ->options(fn () => Port::orderBy('name')->get()
                                ->mapWithKeys(fn (Port $port) => [$port->id => "{$port->name} ({$port->gender->value})"]))
                            ->searchable()
                            ->required(),
                        TextInput::make('amount')
                            ->numeric()
                            ->label('Amount')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        $this->getOwnerRecord()->ports()->attach($data['port_id'], [
                            'amount' => $data['amount']
                        ]);
                    }),
            ])
            ->recordActions([
                Action::make('edit')
                    ->label('Edit')
                    ->form(fn (Port $record) => [
                        TextInput::make('amount')
                            ->numeric()
                            ->label('Amount')
                            ->default($record->pivot->amount)
                            ->required(),
                    ])
                    ->action(function (Port $record, array $data) {
                        $record->pivot->update(['amount' => $data['amount']]);
                    }),
                \Filament\Actions\DetachAction::make(),
            ]);
    }
}
