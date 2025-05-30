<?php

namespace App\Filament\Resources\InvoiceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('amount')
                    ->label('Amount (RM)')
                    ->required()
                    ->numeric()
                    ->prefix('RM'),
                Forms\Components\Select::make('payment_method')
                    ->options([
                        'toyyibpay' => 'ToyyibPay',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'other' => 'Other',
                    ])
                    ->required()
                    ->default('toyyibpay'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'failed' => 'Failed',
                    ])
                    ->required()
                    ->default('pending'),
                Forms\Components\TextInput::make('transaction_reference')
                    ->maxLength(255),
                Forms\Components\TextInput::make('bill_code')
                    ->maxLength(255),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label('Paid Date/Time'),
                Forms\Components\Textarea::make('payment_details')
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
                Tables\Columns\TextColumn::make('amount')
                    ->money('myr')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => ['cancelled', 'failed'],
                    ]),
                Tables\Columns\TextColumn::make('payment_method')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'toyyibpay' => 'ToyyibPay',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'other' => 'Other',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('transaction_reference')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('bill_code')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('paid_at')
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
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'failed' => 'Failed',
                    ]),
                Tables\Filters\SelectFilter::make('payment_method')
                    ->options([
                        'toyyibpay' => 'ToyyibPay',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'other' => 'Other',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\Action::make('create_toyyibpay')
                    ->label('Create ToyyibPay Payment')
                    ->icon('heroicon-o-credit-card')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->label('Amount (RM)')
                            ->required()
                            ->numeric()
                            ->prefix('RM')
                            ->default(fn ($livewire) => $livewire->getOwnerRecord()->total_amount),
                    ])
                    ->action(function (array $data, $livewire) {
                        $invoice = $livewire->getOwnerRecord();
                        
                        // Create a payment record
                        $payment = $invoice->payments()->create([
                            'amount' => $data['amount'],
                            'payment_method' => 'toyyibpay',
                            'status' => 'pending',
                        ]);
                        
                        // Redirect to the payment controller that will handle ToyyibPay integration
                        // This is just a placeholder - in reality, you'd call the ToyyibPayService
                        return redirect()->route('admin.payments.toyyibpay.create', ['payment' => $payment->id]);
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('check_status')
                    ->label('Check Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->visible(fn ($record) => $record->payment_method === 'toyyibpay' && $record->status === 'pending' && $record->bill_code)
                    ->action(function ($record) {
                        // In a real implementation, this would call ToyyibPayService to check status
                        // This is a placeholder
                        return redirect()->route('admin.payments.toyyibpay.check', ['billCode' => $record->bill_code]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
