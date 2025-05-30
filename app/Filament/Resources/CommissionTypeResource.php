<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionTypeResource\Pages;
use App\Filament\Resources\CommissionTypeResource\RelationManagers;
use App\Models\CommissionType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CommissionTypeResource extends Resource
{
    protected static ?string $model = CommissionType::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Basic Information Section
                Forms\Components\Section::make('Commission Type Details')
                    ->description('Basic information about this commission type')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Commission Type Name')
                                    ->required()
                                    ->prefixIcon('heroicon-o-tag')
                                    ->placeholder('e.g., Standard Commission, Referral Bonus')
                                    ->helperText('A unique name that identifies this commission type')
                                    ->maxLength(255),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Active')
                                            ->helperText('Whether this commission type can be used')
                                            ->default(true)
                                            ->required(),

                                        Forms\Components\Toggle::make('is_default')
                                            ->label('Default Type')
                                            ->helperText('Only one commission type can be the default')
                                            ->default(false),
                                    ]),
                            ]),

                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('description')
                                    ->label('Description')
                                    ->placeholder('Describe how and when this commission type applies')
                                    ->helperText('Detailed information about this commission type and when it should be used')
                                    ->maxLength(65535)
                                    ->rows(3),
                            ]),
                    ])
                    ->collapsible(),

                // Commission Structure Section
                Forms\Components\Section::make('Commission Structure')
                    ->description('Fixed and percentage-based commission structure details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('fixed_amount')
                                    ->label('Fixed Amount')
                                    ->numeric()
                                    ->prefix('RM')
                                    ->prefixIcon('heroicon-o-banknotes')
                                    ->placeholder('0.00')
                                    ->helperText('Fixed amount paid regardless of invoice value')
                                    ->default(0)
                                    ->minValue(0)
                                    ->required(),

                                Forms\Components\TextInput::make('percentage_rate')
                                    ->label('Percentage Rate')
                                    ->numeric()
                                    ->suffix('%')
                                    ->prefixIcon('heroicon-o-chart-bar')
                                    ->placeholder('0.00')
                                    ->helperText('Percentage of total invoice amount')
                                    ->default(0)
                                    ->minValue(0)
                                    ->maxValue(100)
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('min_invoice_amount')
                                    ->label('Minimum Invoice Amount')
                                    ->prefixIcon('heroicon-o-arrow-trending-up')
                                    ->prefix('RM')
                                    ->placeholder('0.00')
                                    ->helperText('Minimum invoice amount for this commission to apply (leave empty for no minimum)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->nullable(),

                                Forms\Components\TextInput::make('max_invoice_amount')
                                    ->label('Maximum Invoice Amount')
                                    ->prefixIcon('heroicon-o-arrow-trending-down')
                                    ->prefix('RM')
                                    ->placeholder('0.00')
                                    ->helperText('Maximum invoice amount for this commission to apply (leave empty for no maximum)')
                                    ->numeric()
                                    ->minValue(0)
                                    ->nullable(),
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
                    ->searchable(),

                Tables\Columns\TextColumn::make('fixed_amount')
                    ->money('myr')
                    ->sortable(),

                Tables\Columns\TextColumn::make('percentage_rate')
                    ->suffix('%')
                    ->sortable(),

                Tables\Columns\TextColumn::make('min_invoice_amount')
                    ->label('Min Amount')
                    ->formatStateUsing(fn ($state) => $state ? 'RM ' . number_format($state, 2) : '-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('max_invoice_amount')
                    ->label('Max Amount')
                    ->formatStateUsing(fn ($state) => $state ? 'RM ' . number_format($state, 2) : '-')
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
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
                Tables\Filters\TrashedFilter::make(),

                Tables\Filters\Filter::make('is_active')
                    ->label('Active Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_active', true))
                    ->toggle(),

                Tables\Filters\Filter::make('is_default')
                    ->label('Default Only')
                    ->query(fn (Builder $query): Builder => $query->where('is_default', true))
                    ->toggle(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ForceDeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AgentsRelationManager::class,
            RelationManagers\CommissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissionTypes::route('/'),
            'create' => Pages\CreateCommissionType::route('/create'),
            'edit' => Pages\EditCommissionType::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
