<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Filament\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    
    protected static ?string $navigationGroup = 'Finance';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Basic Payment Information Section
                Forms\Components\Section::make('Payment Information')
                    ->description('Invoice and payment amount details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('invoice_id')
                                    ->label('Invoice')
                                    ->relationship('invoice', 'invoice_number')
                                    ->searchable()
                                    ->required()
                                    ->prefixIcon('heroicon-o-document-text')
                                    ->helperText('Select the invoice this payment is for')
                                    ->createOptionForm([
                                        Forms\Components\Select::make('order_id')
                                            ->label('Order Reference')
                                            ->relationship('order', 'reference_number')
                                            ->required()
                                            ->prefixIcon('heroicon-o-shopping-bag'),
                                            
                                        Forms\Components\TextInput::make('total_amount')
                                            ->label('Total Amount')
                                            ->required()
                                            ->numeric()
                                            ->prefix('RM')
                                            ->prefixIcon('heroicon-o-banknotes')
                                            ->placeholder('0.00'),
                                            
                                        Forms\Components\DatePicker::make('due_date')
                                            ->label('Due Date')
                                            ->required()
                                            ->default(now()->addDays(14))
                                            ->prefixIcon('heroicon-o-calendar'),
                                    ]),
                                    
                                Forms\Components\TextInput::make('amount')
                                    ->label('Payment Amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix('RM')
                                    ->prefixIcon('heroicon-o-currency-dollar')
                                    ->placeholder('0.00')
                                    ->helperText('Amount being paid toward the invoice'),
                            ]),
                    ])
                    ->collapsible(),
                    
                // Payment Method and Status Section    
                Forms\Components\Section::make('Payment Method and Status')
                    ->description('How the payment was made and its current status')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('payment_method')
                                    ->label('Payment Method')
                                    ->options([
                                        'toyyibpay' => 'ToyyibPay',
                                        'bank_transfer' => 'Bank Transfer',
                                        'cash' => 'Cash',
                                        'other' => 'Other',
                                    ])
                                    ->required()
                                    ->default('toyyibpay')
                                    ->prefixIcon('heroicon-o-credit-card'),
                                    
                                Forms\Components\Select::make('status')
                                    ->label('Payment Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                        'failed' => 'Failed',
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->prefixIcon(function (string $state): string {
                                        return match ($state) {
                                            'completed' => 'heroicon-o-check-circle',
                                            'pending' => 'heroicon-o-clock',
                                            'cancelled' => 'heroicon-o-x-circle',
                                            'failed' => 'heroicon-o-exclamation-circle',
                                            default => 'heroicon-o-question-mark-circle',
                                        };
                                    }),
                            ]),
                    ])
                    ->collapsible(),
                    
                // Transaction Details Section    
                Forms\Components\Section::make('Transaction Details')
                    ->description('Payment processing details and reference information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('transaction_reference')
                                    ->label('Transaction Reference')
                                    ->helperText('Reference number from payment provider')
                                    ->placeholder('e.g., TXN12345')
                                    ->prefixIcon('heroicon-o-document-duplicate')
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('bill_code')
                                    ->label('Bill Code')
                                    ->helperText('Bill code for ToyyibPay payments')
                                    ->placeholder('e.g., v5stu9qr3k')
                                    ->prefixIcon('heroicon-o-hashtag')
                                    ->maxLength(255),
                            ]),
                            
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\DateTimePicker::make('paid_at')
                                    ->label('Payment Date/Time')
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->helperText('When the payment was received/processed'),
                            ]),
                            
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('payment_details')
                                    ->label('Payment Details')
                                    ->helperText('JSON details from payment provider')
                                    ->placeholder('{"id":"12345","status":"success","method":"card"}')
                                    ->rows(3),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice.invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice.order.reference_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('invoice.order.client.user.name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('myr')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger' => ['cancelled', 'failed'],
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'toyyibpay' => 'ToyyibPay',
                        'bank_transfer' => 'Bank Transfer',
                        'cash' => 'Cash',
                        'other' => 'Other',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('bill_code')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('transaction_reference')
                    ->label('Transaction ID')
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
                Tables\Filters\Filter::make('paid')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('paid_at')),
                Tables\Filters\Filter::make('unpaid')
                    ->query(fn (Builder $query): Builder => $query->whereNull('paid_at')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('check_status')
                    ->label('Check ToyyibPay Status')
                    ->icon('heroicon-o-arrow-path')
                    ->color('primary')
                    ->visible(fn ($record) => $record->payment_method === 'toyyibpay' && $record->status === 'pending' && $record->bill_code)
                    ->action(function ($record) {
                        // In a real implementation, this would call ToyyibPayService to check status
                        // This is just a placeholder for the admin interface
                        try {
                            // Simulate status check from ToyyibPay
                            if ($record->bill_code) {
                                // In production, you'd actually call the ToyyibPay API here
                                $record->update([
                                    'status' => 'completed',
                                    'paid_at' => now(),
                                    // Normally you'd update the transaction reference and payment details here
                                ]);
                                
                                // Update invoice status if payment is now complete
                                if ($record->invoice && $record->status === 'completed') {
                                    $record->invoice->update(['status' => 'paid']);
                                }
                                
                                // Normally you'd return a notification of success
                            }
                        } catch (\Exception $e) {
                            // Error handling would go here
                        }
                    }),
                Tables\Actions\Action::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'completed',
                            'paid_at' => now(),
                        ]);
                        
                        // Update invoice status
                        if ($record->invoice) {
                            $record->invoice->update(['status' => 'paid']);
                        }
                    }),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
            'create' => Pages\CreatePayment::route('/create'),
            'edit' => Pages\EditPayment::route('/{record}/edit'),
        ];
    }
}
