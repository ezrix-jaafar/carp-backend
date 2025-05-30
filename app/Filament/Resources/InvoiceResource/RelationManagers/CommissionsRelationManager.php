<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

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
                Forms\Components\Select::make('agent_id')
                    ->relationship('agent', 'id')
                    ->getOptionLabelFromRecordUsing(fn ($record) => 
                        optional($record->user)->name ?: "Agent #{$record->id}")
                    ->searchable()
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
                    ->required()
                    ->default('pending'),
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
                Tables\Columns\TextColumn::make('agent.user.name')
                    ->label('Agent')
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
                Tables\Actions\Action::make('calculate_commission')
                    ->label('Calculate Commission')
                    ->icon('heroicon-o-calculator')
                    ->action(function ($livewire) {
                        $invoice = $livewire->getOwnerRecord();
                        $order = $invoice->order;
                        
                        if (!$order->agent) {
                            // No agent assigned, cannot calculate commission
                            return;
                        }
                        
                        $agent = $order->agent;
                        $fixed = $agent->fixed_commission;
                        $percentage = $agent->percentage_commission;
                        $total = $fixed + ($percentage / 100 * $invoice->total_amount);
                        
                        // Create a commission record
                        $invoice->commissions()->create([
                            'agent_id' => $agent->id,
                            'fixed_amount' => $fixed,
                            'percentage' => $percentage,
                            'total_commission' => $total,
                            'status' => 'pending',
                        ]);
                    })
                    ->requiresConfirmation(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
