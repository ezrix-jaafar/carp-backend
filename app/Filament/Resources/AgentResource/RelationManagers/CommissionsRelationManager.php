<?php

namespace App\Filament\Resources\AgentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CommissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'commissions';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('invoice_id')
                    ->relationship('invoice', 'invoice_number')
                    ->required(),
                Forms\Components\TextInput::make('fixed_amount')
                    ->label('Fixed Amount (RM)')
                    ->required()
                    ->numeric()
                    ->prefix('RM'),
                Forms\Components\TextInput::make('percentage')
                    ->label('Percentage (%)')
                    ->required()
                    ->numeric()
                    ->suffix('%'),
                Forms\Components\TextInput::make('total_commission')
                    ->label('Total Commission (RM)')
                    ->required()
                    ->numeric()
                    ->prefix('RM'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label('Paid Date/Time'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invoice.order.reference_number')
                    ->label('Order #')
                    ->searchable(),
                Tables\Columns\TextColumn::make('fixed_amount')
                    ->label('Fixed Amount')
                    ->money('myr')
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage')
                    ->label('Percentage')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_commission')
                    ->label('Total Commission')
                    ->money('myr')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Paid Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('paid')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('paid_at')),
                Tables\Filters\Filter::make('unpaid')
                    ->query(fn (Builder $query): Builder => $query->whereNull('paid_at')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_selected_as_paid')
                        ->label('Mark Selected as Paid')
                        ->icon('heroicon-o-banknotes')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each(function ($record) {
                                if ($record->status === 'pending') {
                                    $record->update([
                                        'status' => 'paid',
                                        'paid_at' => now(),
                                    ]);
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
