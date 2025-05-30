<?php

namespace App\Filament\Resources\CarpetResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\AddonService;

class AddonServicesRelationManager extends RelationManager
{
    protected static string $relationship = 'addonServices';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('addon_service_id')
                    ->label('Addon Service')
                    ->options(AddonService::where('is_active', true)->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function (callable $set, $state) {
                        if (!$state) return;
                        
                        $addonService = AddonService::find($state);
                        if (!$addonService) return;
                        
                        // Set a default price based on the service's standard price
                        $carpet = $this->getOwnerRecord();
                        $defaultPrice = $addonService->calculatePrice($carpet);
                        $set('price_override', $defaultPrice);
                    }),
                
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
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Service')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_per_square_foot')
                    ->label('Per Sq Ft')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('standard_price')
                    ->label('Standard Price')
                    ->getStateUsing(function ($record) {
                        $carpet = $this->getOwnerRecord();
                        return 'RM ' . number_format($record->calculatePrice($carpet), 2);
                    }),
                
                Tables\Columns\TextColumn::make('pivot.price_override')
                    ->label('Price Override')
                    ->money('MYR'),
                    
                Tables\Columns\TextColumn::make('pivot.notes')
                    ->label('Notes')
                    ->limit(30),
                    
                Tables\Columns\TextColumn::make('effective_price')
                    ->label('Effective Price')
                    ->getStateUsing(function ($record) {
                        $priceOverride = $record->pivot->price_override;
                        $carpet = $this->getOwnerRecord();
                        $price = $priceOverride ?: $record->calculatePrice($carpet);
                        return 'RM ' . number_format($price, 2);
                    })
                    ->weight('bold'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_per_square_foot')
                    ->label('Pricing Type')
                    ->placeholder('All Types')
                    ->trueLabel('Per Square Foot')
                    ->falseLabel('Fixed Price'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->recordSelectSearchColumns(['name', 'description'])
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect()
                            ->label('Addon Service')
                            ->reactive()
                            ->afterStateUpdated(function (callable $set, $state) {
                                if (!$state) return;
                                
                                $addonService = AddonService::find($state);
                                if (!$addonService) return;
                                
                                // Set a default price based on the service's standard price
                                $carpet = $this->getOwnerRecord();
                                $defaultPrice = $addonService->calculatePrice($carpet);
                                $set('price_override', $defaultPrice);
                            }),
                        
                        Forms\Components\TextInput::make('price_override')
                            ->label('Price Override (RM)')
                            ->helperText('Optional. If set, this will override the calculated price')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('RM'),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Service Notes')
                            ->placeholder('Special instructions or notes for this service')
                            ->maxLength(65535),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->form(fn (Tables\Actions\EditAction $action): array => [
                        Forms\Components\TextInput::make('price_override')
                            ->label('Price Override (RM)')
                            ->helperText('Optional. If set, this will override the calculated price')
                            ->numeric()
                            ->minValue(0)
                            ->prefix('RM'),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Service Notes')
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
