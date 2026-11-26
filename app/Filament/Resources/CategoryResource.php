<?php

namespace App\Filament\Resources;

use Filament\Schemas\Schema;
use App\Filament\Resources\CategoryResource\Pages;
use App\Filament\Resources\CategoryResource\RelationManagers;
use App\Models\Category;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Forms\Components\TextInput::make('name')
                    ->label('Název')
                    ->required(),
                Forms\Components\Select::make('parent_id')
                    ->label('Sekce')
                    ->helperText('Prázdné = hlavní sekce v menu (Zvuk, Světla…). Jinak podkategorie vybrané sekce.')
                    ->options(fn (?Category $record) => Category::roots()
                        ->when($record, fn ($q) => $q->whereKeyNot($record->getKey()))
                        ->ordered()
                        ->pluck('name', 'id'))
                    ->disabled(fn (?Category $record) => $record?->children()->exists() ?? false)
                    ->placeholder('— hlavní sekce —'),
                Forms\Components\TextInput::make('position')
                    ->label('Pořadí v menu')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Název')
                    ->searchable(),
                Tables\Columns\TextColumn::make('parent.name')
                    ->label('Sekce')
                    ->placeholder('—')
                    ->sortable(),
                Tables\Columns\TextColumn::make('position')
                    ->label('Pořadí')
                    ->sortable(),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Produktů')
                    ->counts('products'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort(fn ($query) => $query->orderByRaw('COALESCE(parent_id, id)')->orderByRaw('parent_id IS NOT NULL')->orderBy('position'))
            ->filters([
                //
            ])
            ->recordActions([
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
