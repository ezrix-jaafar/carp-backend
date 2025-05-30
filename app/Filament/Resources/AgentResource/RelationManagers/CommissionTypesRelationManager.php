<?php

namespace App\Filament\Resources\AgentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CommissionTypesRelationManager extends RelationManager
{
    protected static string $relationship = 'commissionTypes';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Agent-Specific Commission Settings')
                    ->schema([
                        Forms\Components\TextInput::make('fixed_amount_override')
                            ->label('Fixed Amount Override (RM)')
                            ->helperText('Leave empty to use the default amount from the commission type')
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),
                        
                        Forms\Components\TextInput::make('percentage_rate_override')
                            ->label('Percentage Rate Override (%)')
                            ->helperText('Leave empty to use the default rate from the commission type')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->nullable(),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Whether this commission type is active for this agent')
                            ->default(true)
                            ->required(),
                        
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('fixed_amount')
                    ->label('Base Fixed')
                    ->money('myr'),
                
                Tables\Columns\TextColumn::make('percentage_rate')
                    ->label('Base Rate')
                    ->suffix('%'),
                
                Tables\Columns\TextColumn::make('pivot.fixed_amount_override')
                    ->label('Fixed Override')
                    ->money('myr')
                    ->placeholder('Default'),
                
                Tables\Columns\TextColumn::make('pivot.percentage_rate_override')
                    ->label('Rate Override')
                    ->formatStateUsing(fn ($state) => $state !== null ? $state . '%' : 'Default')
                    ->placeholder('Default'),
                
                Tables\Columns\IconColumn::make('pivot.is_active')
                    ->label('Active')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('min_invoice_amount')
                    ->label('Min Invoice')
                    ->formatStateUsing(fn ($state) => $state ? 'RM ' . number_format($state, 2) : '-'),
                
                Tables\Columns\TextColumn::make('max_invoice_amount')
                    ->label('Max Invoice')
                    ->formatStateUsing(fn ($state) => $state ? 'RM ' . number_format($state, 2) : '-'),
                
                Tables\Columns\IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\Filter::make('active')
                    ->query(fn (Builder $query): Builder => $query->wherePivot('is_active', true))
                    ->label('Active Only')
                    ->toggle(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        Forms\Components\TextInput::make('fixed_amount_override')
                            ->label('Fixed Amount Override (RM)')
                            ->helperText('Leave empty to use the default amount from the commission type')
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),
                        Forms\Components\TextInput::make('percentage_rate_override')
                            ->label('Percentage Rate Override (%)')
                            ->helperText('Leave empty to use the default rate from the commission type')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(100)
                            ->nullable(),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Whether this commission type is active for this agent')
                            ->default(true)
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
