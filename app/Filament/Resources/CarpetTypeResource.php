<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CarpetTypeResource\Pages;
use App\Filament\Resources\CarpetTypeResource\RelationManagers;
use App\Models\CarpetType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CarpetTypeResource extends Resource
{
    protected static ?string $model = CarpetType::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';
    
    protected static ?string $navigationGroup = 'Settings';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Carpet Type Details')
                    ->description('Basic information about this carpet type')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Type Name')
                                    ->required()
                                    ->prefixIcon('heroicon-o-tag')
                                    ->placeholder('e.g., Persian, Wool, Synthetic')
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
                                    ->placeholder('Describe the carpet type, materials, common uses, etc.')
                                    ->maxLength(65535),
                            ]),
                            
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Toggle::make('is_per_square_foot')
                                    ->label('Price Per Square Foot')
                                    ->helperText('If enabled, the price will be per square foot. If disabled, it will be a fixed price.')
                                    ->default(false)
                                    ->required()
                                    ->inline(false),
                            ]),
                    ])
                    ->collapsible(false),
                    
                Forms\Components\Section::make('Cleaning Details')
                    ->description('Special handling and cleaning information')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('cleaning_instructions')
                                    ->label('Cleaning Instructions')
                                    ->placeholder('Special cleaning instructions for this type of carpet...')
                                    ->helperText('Include any specific cleaning processes, chemicals to avoid, or handling precautions')
                                    ->maxLength(65535),
                            ]),
                    ])
                    ->collapsible(),
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
                    ->label('Square Foot Pricing')
                    ->boolean()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('price')
                    ->money('MYR')
                    ->sortable()
                    ->description(fn (CarpetType $record): string => $record->is_per_square_foot ? 'per sq ft' : 'fixed'),
                    
                Tables\Columns\TextColumn::make('carpets_count')
                    ->label('Carpets')
                    ->counts('carpets')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_per_square_foot')
                    ->label('Pricing Type')
                    ->placeholder('All Types')
                    ->trueLabel('Per Square Foot')
                    ->falseLabel('Fixed Price'),
                    
                Tables\Filters\TrashedFilter::make(),
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
            RelationManagers\CarpetsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCarpetTypes::route('/'),
            'create' => Pages\CreateCarpetType::route('/create'),
            'edit' => Pages\EditCarpetType::route('/{record}/edit'),
        ];
    }
}
