<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use App\Filament\Resources\ReservationResource\Pages;
use App\Filament\Resources\ReservationResource\RelationManagers;
use App\Models\Reservation;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\IconEntry;

class ReservationResource extends Resource
{
    protected static ?string $model = Reservation::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->maxLength(255)
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255)
                    ->requiredWithout('phone')
                    // email/phone columns are NOT NULL: store '' when left empty
                    ->dehydrateStateUsing(fn ($state) => $state ?? ''),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(50)
                    ->requiredWithout('email')
                    ->dehydrateStateUsing(fn ($state) => $state ?? ''),
                Forms\Components\TextInput::make('event_place')
                    ->label('Event place')
                    ->maxLength(255),
                Forms\Components\Select::make('event_type')
                    ->label('Event type')
                    ->options(array_combine(\App\Services\ReservationBooking::EVENT_TYPES, \App\Services\ReservationBooking::EVENT_TYPES)),
                Forms\Components\Toggle::make('delivery')
                    ->label('Delivery & pick-up'),
                Forms\Components\Toggle::make('service')
                    ->label('Installation / operator (extra)'),
                Forms\Components\Select::make('delivery_zone')
                    ->label('Delivery zone')
                    ->options(collect(config('contact.delivery_zones'))->map(fn ($z) => $z['label'])->all()),
                Forms\Components\TextInput::make('delivery_price')
                    ->label('Delivery price (included in price)')
                    ->numeric()
                    ->suffix('CZK'),
                Forms\Components\DateTimePicker::make('start_date')
                    ->required()
                    ->rule('before:end_date'),
                Forms\Components\DateTimePicker::make('end_date')
                    ->required()
                    ->rule('after:start_date'),
                Forms\Components\TextInput::make('price')
                    ->label('Price')
                    ->numeric()
                    ->minValue(0)
                    ->step(0.01)
                    ->suffix('CZK')
                    ->required(),
                Forms\Components\TextInput::make('note')
                    ->maxLength(1000)
                    ->nullable(),
                Forms\Components\Toggle::make('is_active')
                    ->required(),
                Forms\Components\Toggle::make('is_paid')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_place')
                    ->label('Place')
                    ->searchable(),
                Tables\Columns\TextColumn::make('event_type')
                    ->label('Type')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('delivery')
                    ->boolean(),
                Tables\Columns\TextColumn::make('delivery_zone')
                    ->label('Zone')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('source')
                    ->badge()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('note')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price')
                    ->money('CZK')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_paid')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('is_active')
                    ->options([1 => 'Active', 0 => 'Inactive']),
                Tables\Filters\SelectFilter::make('is_paid')
                    ->options([1 => 'Paid', 0 => 'Unpaid']),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ReservationItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReservations::route('/'),
            'create' => Pages\CreateReservation::route('/create'),
            'view' => Pages\ViewReservation::route('/{record}'),
            'edit' => Pages\EditReservation::route('/{record}/edit'),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('email'),
                TextEntry::make('phone'),
                TextEntry::make('start_date')->dateTime(),
                TextEntry::make('end_date')->dateTime(),
                TextEntry::make('note'),
                TextEntry::make('price')->money('CZK'),
                IconEntry::make('is_active')->boolean(),
                IconEntry::make('is_paid')->boolean(),
            ]);
    }
}
