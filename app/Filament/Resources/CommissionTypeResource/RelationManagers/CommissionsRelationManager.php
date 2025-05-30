<?php

namespace App\Filament\Resources\CommissionTypeResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CommissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'commissions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Commission Details')
                    ->schema([
                        Forms\Components\TextInput::make('fixed_amount')
                            ->label('Fixed Amount (RM)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\TextInput::make('percentage')
                            ->label('Percentage Rate (%)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\TextInput::make('total_commission')
                            ->label('Total Commission (RM)')
                            ->numeric()
                            ->disabled()
                            ->dehydrated(false),
                        
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                        
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Paid At')
                            ->nullable(),
                        
                        Forms\Components\Textarea::make('notes')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('agent.user.name')
                    ->label('Agent')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('invoice.total_amount')
                    ->label('Invoice Amount')
                    ->money('myr')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('fixed_amount')
                    ->money('myr')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('percentage')
                    ->suffix('%')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('total_commission')
                    ->money('myr')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->headerActions([
                // No creation action as commissions are created by the system
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
