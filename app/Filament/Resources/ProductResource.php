<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Port;
use App\Models\Product;
use App\Models\Tag;
use Filament\Forms;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\RichEditor::make('description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('status')
                    ->required(),
                Forms\Components\Select::make('category_id')
                    ->relationship('category', 'name')
                    ->required(),
                Forms\Components\TextInput::make('price_per_day')
                    ->label('Price per day')
                    ->numeric()
                    ->prefix('Kč')
                    ->required()
                    ->default(0),
                Forms\Components\TextInput::make('stock')
                    ->label('Stock')
                    ->numeric()
                    ->minValue(0)
                    ->required()
                    ->default(0),
//                Forms\Components\Select::make('ports')
//                    ->multiple()
//                    ->preload()
//                    ->pivotData(['amount' => 1])
//                    ->createOptionForm(Port::getForm())
//                    ->relationship('ports', 'name')
//                    ->required(),
                Forms\Components\Select::make('tags')
                    ->multiple()
                    ->preload()
                    ->createOptionForm(Tag::getForm())
                    ->relationship('tags', 'name'),
                Forms\Components\Grid::make(2) // Two columns
                ->schema([
                    SpatieMediaLibraryFileUpload::make('avatar')
                        ->image()
                        ->imageEditor(),
                    SpatieMediaLibraryFileUpload::make('gallery')
                        ->collection('product-images')
                        ->multiple()
                        ->reorderable()
                        ->image()
                        ->responsiveImages()
                        ->imageEditor()
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->numeric()
                    ->sortable(),
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
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PortsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
