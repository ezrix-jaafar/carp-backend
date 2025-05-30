<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AddonServiceResource\Pages;
use App\Filament\Resources\AddonServiceResource\RelationManagers;
use App\Models\AddonService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AddonServiceResource extends Resource
{
    protected static ?string $model = AddonService::class;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    
    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Addon Service Details')
                    ->description('Basic information about this addon service')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Service Name')
                                    ->required()
                                    ->prefixIcon('heroicon-o-sparkles')
                                    ->placeholder('e.g., Stain Protection, Deep Clean')
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('price')
                                    ->label(fn (callable $get) => $get('is_per_square_foot') ? 'Price Per Square Foot' : 'Fixed Price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->required()
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->prefix('RM')
                                    ->placeholder('0.00'),
                            ]),
                            
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->placeholder('Describe what this addon service provides, its benefits, etc.')
                                    ->maxLength(65535),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Toggle::make('is_per_square_foot')
                                    ->label('Price Per Square Foot')
                                    ->helperText('If enabled, the price will be per square foot of the carpet. If disabled, it will be a fixed price.')
                                    ->default(false)
                                    ->required()
                                    ->inline(false),
                                    
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active Status')
                                    ->helperText('Whether this addon service can be selected for new carpets')
                                    ->default(true)
                                    ->inline(false),
                            ]),
                    ])
                    ->collapsible(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_per_square_foot')
                    ->label('Per Sq Ft Pricing')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('price')
                    ->money('MYR')
                    ->sortable()
                    ->description(fn (AddonService $record): string => $record->is_per_square_foot ? 'per sq ft' : 'fixed'),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_per_square_foot')
                    ->label('Pricing Type')
                    ->placeholder('All Types')
                    ->trueLabel('Per Square Foot')
                    ->falseLabel('Fixed Price'),
                    
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive'),
                    
                Tables\Filters\TrashedFilter::make()
            ])
            ->actions([
                Tables\Actions\EditAction::make()
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                ])
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\CarpetsRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAddonServices::route('/'),
            'create' => Pages\CreateAddonService::route('/create'),
            'edit' => Pages\EditAddonService::route('/{record}/edit')
        ];
    }
}
