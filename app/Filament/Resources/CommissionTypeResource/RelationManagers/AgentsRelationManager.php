<?php

namespace App\Filament\Resources\CommissionTypeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class AgentsRelationManager extends RelationManager
{
    protected static string $relationship = 'agents';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Agent Commission Settings')
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
            ->recordTitleAttribute('user.name')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                
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
                
                Tables\Columns\TextColumn::make('pivot.notes')
                    ->label('Notes')
                    ->limit(30),
            ])
            ->filters([
                //
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
