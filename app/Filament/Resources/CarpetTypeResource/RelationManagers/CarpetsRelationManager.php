<?php

namespace App\Filament\Resources\CarpetTypeResource\RelationManagers;

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
                    
                Forms\Components\TextInput::make('width')
                    ->label('Width (ft)')
                    ->numeric()
                    ->minValue(0.1)
                    ->step(0.1)
                    ->required(),
                    
                Forms\Components\TextInput::make('length')
                    ->label('Length (ft)')
                    ->numeric()
                    ->minValue(0.1)
                    ->step(0.1)
                    ->required(),
                    
                Forms\Components\TextInput::make('color')
                    ->maxLength(50),
                    
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'picked_up' => 'Picked Up',
                        'in_cleaning' => 'In Cleaning',
                        'cleaned' => 'Cleaned',
                        'delivered' => 'Delivered',
                    ])
                    ->required(),
                    
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535),
                    
                Forms\Components\TextInput::make('additional_charges')
                    ->label('Additional Charges (RM)')
                    ->numeric()
                    ->minValue(0)
                    ->default(0),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('qr_code')
            ->columns([
                Tables\Columns\TextColumn::make('qr_code')
                    ->searchable()
                    ->limit(20),
                
                Tables\Columns\TextColumn::make('order.reference_number')
                    ->label('Order')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('width')
                    ->label('Width (ft)')
                    ->numeric(2)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('length')
                    ->label('Length (ft)')
                    ->numeric(2)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('square_footage')
                    ->label('Area (sq ft)')
                    ->numeric(2)
                    ->getStateUsing(fn ($record) => $record->square_footage)
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'picked_up' => 'info',
                        'in_cleaning' => 'warning',
                        'cleaned' => 'success',
                        'delivered' => 'primary',
                        default => 'gray',
                    }),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
