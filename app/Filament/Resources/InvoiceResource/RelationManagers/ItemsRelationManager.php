<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Item')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('item_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->colors([
                        'primary' => 'carpet_base',
                        'success' => 'addon_service',
                        'warning' => 'other',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->numeric(),
                Tables\Columns\TextColumn::make('unit')
                    ->label('Unit')
                    ->formatStateUsing(fn (?string $state): string => $state ? ucfirst(str_replace('_', ' ', $state)) : ''),
                Tables\Columns\TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->prefix('RM ')
                    ->numeric(2),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->prefix('RM ')
                    ->numeric(2)
                    ->sortable(),
                Tables\Columns\TextColumn::make('carpet.qr_code')
                    ->label('Carpet')
                    ->toggleable()
                    ->searchable(),
            ])
            ->defaultSort('sort_order', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('item_type')
                    ->options([
                        'carpet_base' => 'Carpet Base Price',
                        'addon_service' => 'Addon Service',
                        'other' => 'Other Charges',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Add Manual Line Item')
                    ->modalHeading('Add a custom line item')
                    ->modalDescription('This will add a manual line item to the invoice that is not tied to any specific carpet.'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
