<?php

namespace App\Filament\Resources\AddonServiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Order;
use App\Models\CarpetType;

class CarpetsRelationManager extends RelationManager
{
    protected static string $relationship = 'carpets';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('order_id')
                    ->label('Order')
                    ->options(Order::all()->pluck('reference_number', 'id'))
                    ->searchable()
                    ->required(),
                    
                Forms\Components\Select::make('carpet_type_id')
                    ->label('Carpet Type')
                    ->relationship('carpetType', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                
                Forms\Components\TextInput::make('width')
                    ->label('Width (ft)')
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0.1)
                    ->required(),
                    
                Forms\Components\TextInput::make('length')
                    ->label('Length (ft)')
                    ->numeric()
                    ->step(0.1)
                    ->minValue(0.1)
                    ->required(),
                
                Forms\Components\TextInput::make('price_override')
                    ->label('Price Override (RM)')
                    ->helperText('If set, this will override the calculated price for this addon service')
                    ->numeric()
                    ->minValue(0)
                    ->prefix('RM'),
                    
                Forms\Components\Textarea::make('notes')
                    ->label('Service Notes')
                    ->placeholder('Special instructions for this addon service')
                    ->maxLength(65535),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('qr_code')
                    ->searchable()
                    ->limit(15),
                    
                Tables\Columns\TextColumn::make('order.reference_number')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('carpetType.name')
                    ->label('Carpet Type')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('square_footage')
                    ->label('Size (sq ft)')
                    ->getStateUsing(fn ($record) => $record->square_footage)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('pivot.price_override')
                    ->label('Price Override')
                    ->money('MYR')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match($state) {
                        'pending' => 'gray',
                        'picked_up' => 'info',
                        'in_cleaning' => 'warning',
                        'cleaned' => 'success',
                        'delivered' => 'primary',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'picked_up' => 'Picked Up',
                        'in_cleaning' => 'In Cleaning',
                        'cleaned' => 'Cleaned',
                        'delivered' => 'Delivered',
                    ]),
                    
                Tables\Filters\SelectFilter::make('order')
                    ->relationship('order', 'reference_number')
                    ->searchable(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\TextInput::make('price_override')
                            ->label('Price Override (RM)')
                            ->helperText('If set, this will override the calculated price for this addon service')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('RM'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Service Notes')
                            ->placeholder('Special instructions for this addon service')
                            ->maxLength(65535),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form(fn (Tables\Actions\EditAction $action): array => [
                        Forms\Components\TextInput::make('price_override')
                            ->label('Price Override (RM)')
                            ->helperText('If set, this will override the calculated price for this addon service')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('RM'),
                        Forms\Components\Textarea::make('notes')
                            ->label('Service Notes')
                            ->placeholder('Special instructions for this addon service')
                            ->maxLength(65535),
                    ]),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
