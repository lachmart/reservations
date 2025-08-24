<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Columns\TextColumn;

class BundleItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'bundleItems';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            TextInput::make('pivot.amount')
                ->numeric()
                ->label('Amount per bundle')
                ->minValue(1)
                ->required(),
        ]);
    }

    public function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Product'),
                TextColumn::make('pivot.amount')->label('Amount per bundle'),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Add product to bundle')
                    ->form([
                        Select::make('product_id')
                            ->label('Product')
                            ->options(Product::query()->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('amount')
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->label('Amount per bundle')
                            ->required(),
                    ])
                    ->action(function (array $data) {
                        // Prevent adding itself as a child
                        $owner = $this->getOwnerRecord();
                        if ((int) $data['product_id'] === (int) $owner->id) {
                            return; // ignore self-reference
                        }
                        $relation = $owner->bundleItems();
                        $existing = $relation->where('products.id', $data['product_id'])->exists();
                        if ($existing) {
                            // Update amount on existing pivot
                            $relation->updateExistingPivot($data['product_id'], [
                                'amount' => (int) $data['amount'],
                            ]);
                        } else {
                            $relation->attach($data['product_id'], [
                                'amount' => (int) $data['amount'],
                            ]);
                        }
                    }),
            ])
            ->actions([
                Action::make('edit')
                    ->label('Edit amount')
                    ->form(function (Product $record) {
                        return [
                            TextInput::make('amount')
                                ->numeric()
                                ->minValue(1)
                                ->label('Amount per bundle')
                                ->default($record->pivot->amount)
                                ->required(),
                        ];
                    })
                    ->action(function (Product $record, array $data) {
                        $record->pivot->update(['amount' => (int) $data['amount']]);
                    }),
                Tables\Actions\DetachAction::make(),
            ]);
    }
}
