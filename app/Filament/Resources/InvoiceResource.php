<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\TaxSetting;
use App\Services\InvoiceRegenerationService;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    
    protected static ?string $navigationGroup = 'Finance';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Basic Invoice Information Section
                Forms\Components\Section::make('Invoice Information')
                    ->description('Basic invoice and order details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('order_id')
                                    ->label('Order Reference')
                                    ->relationship('order', 'reference_number')
                                    ->searchable()
                                    ->required()
                                    ->prefixIcon('heroicon-o-shopping-bag')
                                    ->helperText('Select the order to invoice'),
                                    
                                Forms\Components\TextInput::make('invoice_number')
                                    ->label('Invoice Number')
                                    ->helperText('Auto-generated if left empty')
                                    ->disabled(fn (string $operation): bool => $operation === 'edit')
                                    ->prefixIcon('heroicon-o-document-text')
                                    ->placeholder('e.g., INV-20250517-001')
                                    ->maxLength(255),
                            ]),
                    ])
                    ->collapsible(),
                
                // Financial Details Section    
                Forms\Components\Section::make('Financial Details')
                    ->description('Invoice amounts, tax, and discount information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('subtotal')
                                    ->label('Subtotal')
                                    ->prefix('RM')
                                    ->numeric()
                                    ->prefixIcon('heroicon-o-calculator')
                                    ->placeholder('0.00')
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                    
                                Forms\Components\TextInput::make('total_amount')
                                    ->label('Total Amount')
                                    ->required()
                                    ->prefix('RM')
                                    ->numeric()
                                    ->placeholder('0.00')
                                    ->prefixIcon('heroicon-o-banknotes')
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('discount')
                                    ->label('Discount')
                                    ->prefix(fn (callable $get) => $get('discount_type') === 'percentage' ? '' : 'RM')
                                    ->suffix(fn (callable $get) => $get('discount_type') === 'percentage' ? '%' : '')
                                    ->numeric()
                                    ->prefixIcon('heroicon-o-tag')
                                    ->placeholder('0.00')
                                    ->default(0),
                                    
                                Forms\Components\Select::make('discount_type')
                                    ->label('Discount Type')
                                    ->options([
                                        'fixed' => 'Fixed Amount',
                                        'percentage' => 'Percentage',
                                    ])
                                    ->default('fixed')
                                    ->prefixIcon('heroicon-o-currency-dollar'),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('tax_amount')
                                    ->label('Tax Amount')
                                    ->prefix('RM')
                                    ->numeric()
                                    ->placeholder('0.00')
                                    ->prefixIcon('heroicon-o-scale')
                                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                                    
                                Forms\Components\Select::make('tax_setting_id')
                                    ->label('Tax Setting')
                                    ->relationship('taxSetting', 'name')
                                    ->options(function () {
                                        return TaxSetting::get()->pluck('name', 'id');
                                    })
                                    ->placeholder('No tax')
                                    ->prefixIcon('heroicon-o-chart-bar'),
                            ]),
                            
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Invoice Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'paid' => 'Paid',
                                        'cancelled' => 'Cancelled',
                                        'overdue' => 'Overdue',
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->prefixIcon(function (string $state): string {
                                        return match ($state) {
                                            'paid' => 'heroicon-o-check-circle',
                                            'pending' => 'heroicon-o-clock',
                                            'cancelled' => 'heroicon-o-x-circle',
                                            'overdue' => 'heroicon-o-exclamation-circle',
                                            default => 'heroicon-o-question-mark-circle',
                                        };
                                    }),
                            ]),
                    ])
                    ->collapsible(),
                    
                // Dates Section    
                Forms\Components\Section::make('Important Dates')
                    ->description('Invoice issue and payment due dates')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DatePicker::make('issued_at')
                                    ->label('Issue Date')
                                    ->required()
                                    ->default(now())
                                    ->prefixIcon('heroicon-o-calendar'),
                                    
                                Forms\Components\DatePicker::make('due_date')
                                    ->label('Due Date')
                                    ->required()
                                    ->default(now()->addDays(14))
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->helperText('Default is 14 days from issue date'),
                            ]),
                    ])
                    ->collapsible(),
                    
                // Additional Information Section    
                Forms\Components\Section::make('Additional Information')
                    ->description('Notes and additional details for this invoice')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Invoice Notes')
                                    ->placeholder('Enter any additional information, payment terms, or notes to the client')
                                    ->helperText('These notes will appear on the invoice PDF')
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
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.reference_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.client.user.name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('subtotal')
                    ->label('Subtotal')
                    ->money('myr')
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount')
                    ->label('Discount')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->discount_type === 'percentage') {
                            return $state . '%';
                        }
                        return 'RM ' . number_format($state, 2);
                    })
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('tax_amount')
                    ->label('Tax')
                    ->money('myr')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('myr')
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => ['cancelled', 'overdue'],
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('payments_count')
                    ->label('Payments')
                    ->counts('payments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('issued_at')
                    ->label('Issue Date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
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
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                        'overdue' => 'Overdue',
                    ]),
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => $query->where('due_date', '<', now())->where('status', 'pending')),
                Tables\Filters\Filter::make('has_payments')
                    ->query(fn (Builder $query): Builder => $query->has('payments')),
                Tables\Filters\Filter::make('no_payments')
                    ->query(fn (Builder $query): Builder => $query->doesntHave('payments')),
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
                        ]);
                    }),
                Tables\Actions\Action::make('generate_pdf')
                    ->label('Generate PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => route('admin.invoices.pdf', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('view_payments')
                    ->label('View Payments')
                    ->icon('heroicon-o-credit-card')
                    ->url(fn ($record) => '/admin/payments?invoice_id=' . $record->id)
                    ->openUrlInNewTab(),
                    
                Tables\Actions\Action::make('regenerate_invoice')
                    ->label('Regenerate Invoice')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status !== 'canceled' && $record->payments()->count() === 0)
                    ->form([
                        Forms\Components\Select::make('tax_setting_id')
                            ->label('Tax Type')
                            ->options(function () {
                                return TaxSetting::where('is_active', true)
                                    ->get()
                                    ->pluck('name', 'id')
                                    ->toArray();
                            })
                            ->helperText('Choose applicable tax for this invoice')
                            ->default(fn ($record) => $record->tax_setting_id)
                            ->placeholder('No tax'),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('discount')
                                    ->label('Discount')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(fn ($record) => $record->discount)
                                    ->step(0.01),
                                    
                                Forms\Components\Select::make('discount_type')
                                    ->label('Discount Type')
                                    ->options([
                                        'fixed' => 'Fixed Amount',
                                        'percentage' => 'Percentage (%)',
                                    ])
                                    ->default(fn ($record) => $record->discount_type),
                            ]),
                            
                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->default(fn ($record) => $record->due_date)
                            ->required(),
                            
                        Forms\Components\Textarea::make('notes')
                            ->label('Invoice Notes')
                            ->placeholder('Enter any additional notes to be included on the new invoice')
                            ->default(fn ($record) => $record->notes)
                            ->rows(3),
                    ])
                    ->action(function (array $data, $record): void {
                        $order = $record->order;
                        
                        // Use the regeneration service
                        $regenerationService = app(InvoiceRegenerationService::class);
                        $newInvoice = $regenerationService->regenerateInvoice($order, $record, $data);
                        
                        // Provide feedback using Filament's Notification facade
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Invoice Regenerated')
                            ->body('Invoice regenerated successfully! New invoice: ' . $newInvoice->invoice_number)
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Regenerate Invoice')
                    ->modalDescription('This will cancel the current invoice and create a new one with the updated carpet information. This should only be done if carpets have been modified after the invoice was created.')
                    ->modalSubmitActionLabel('Regenerate Invoice'),
            ])
            ->actions([
                Tables\Actions\Action::make('get_payment_link')
                    ->label('Get Payment Link')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('payment_link')
                            ->label('Payment Link')
                            ->default(function ($record) {
                                return route('payments.checkout', ['invoice' => encrypt($record->id)]);
                            })
                            ->disabled()
                            ->helperText('Select all and copy this link to share with your customer for online payment.')
                            ->rows(2)
                    ]),
                    
                Tables\Actions\Action::make('view_payment_page')
                    ->label('View Payment Page')
                    ->icon('heroicon-o-credit-card')
                    ->color('primary')
                    ->url(fn ($record) => route('payments.checkout', ['invoice' => encrypt($record->id)]))
                    ->openUrlInNewTab(),
                    
                Tables\Actions\Action::make('view_pdf')
                    ->label('View PDF')
                    ->icon('heroicon-o-document')
                    ->color('danger')
                    ->url(fn ($record) => route('admin.invoices.pdf', ['invoice' => $record->id]))
                    ->openUrlInNewTab(),
                    
                Tables\Actions\ViewAction::make(),
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
            RelationManagers\ItemsRelationManager::class,
            RelationManagers\PaymentsRelationManager::class,
            RelationManagers\CommissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
