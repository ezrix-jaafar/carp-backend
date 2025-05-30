<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CommissionResource\Pages;
use App\Filament\Resources\CommissionResource\RelationManagers;
use App\Models\Commission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CommissionResource extends Resource
{
    protected static ?string $model = Commission::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Basic Commission Information Section
                Forms\Components\Section::make('Commission Information')
                    ->description('Agent and invoice details for this commission')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('agent_id')
                                    ->label('Agent')
                                    ->relationship('agent', 'id')
                                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                                        optional($record->user)->name ?: "Agent #{$record->id}")
                                    ->searchable()
                                    ->required()
                                    ->prefixIcon('heroicon-o-user')
                                    ->helperText('Select the agent receiving this commission'),

                                Forms\Components\Select::make('invoice_id')
                                    ->label('Invoice')
                                    ->relationship('invoice', 'invoice_number')
                                    ->searchable()
                                    ->required()
                                    ->prefixIcon('heroicon-o-document-text')
                                    ->helperText('Invoice this commission is based on'),
                            ]),
                    ])
                    ->collapsible(),

                // Commission Calculation Section
                Forms\Components\Section::make('Commission Calculation')
                    ->description('Fixed and percentage-based commission details')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('fixed_amount')
                                    ->label('Fixed Amount')
                                    ->required()
                                    ->numeric()
                                    ->prefix('RM')
                                    ->prefixIcon('heroicon-o-banknotes')
                                    ->placeholder('0.00')
                                    ->helperText('Set amount regardless of invoice total'),

                                Forms\Components\TextInput::make('percentage')
                                    ->label('Percentage')
                                    ->required()
                                    ->numeric()
                                    ->suffix('%')
                                    ->prefixIcon('heroicon-o-chart-bar')
                                    ->placeholder('0.00')
                                    ->helperText('Percentage of invoice amount'),

                                Forms\Components\TextInput::make('total_commission')
                                    ->label('Total Commission')
                                    ->required()
                                    ->numeric()
                                    ->prefix('RM')
                                    ->prefixIcon('heroicon-o-calculator')
                                    ->placeholder('0.00')
                                    ->helperText('Fixed amount + (percentage × invoice amount)')
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, Forms\Get $get, Forms\Set $set) {
                                        // This would normally be calculated automatically
                                        // based on fixed_amount, percentage, and invoice amount
                                    }),
                            ]),
                    ])
                    ->collapsible(),

                // Status and Payment Section
                Forms\Components\Section::make('Status and Payment')
                    ->description('Commission payment status and details')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Commission Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'paid' => 'Paid',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->prefixIcon(function (string $state): string {
                                        return match ($state) {
                                            'paid' => 'heroicon-o-check-circle',
                                            'pending' => 'heroicon-o-clock',
                                            'cancelled' => 'heroicon-o-x-circle',
                                            default => 'heroicon-o-question-mark-circle',
                                        };
                                    }),

                                Forms\Components\DateTimePicker::make('paid_at')
                                    ->label('Payment Date/Time')
                                    ->prefixIcon('heroicon-o-calendar-days')
                                    ->visible(fn (Forms\Get $get) => $get('status') === 'paid')
                                    ->helperText('When the commission was paid to the agent'),
                            ]),

                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Payment Notes')
                                    ->placeholder('Enter any additional notes about this commission payment')
                                    ->helperText('Internal notes for tracking commission details')
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
                Tables\Columns\TextColumn::make('agent.user.name')
                    ->label('Agent')
                    ->searchable()
                    ->sortable(),
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
                    ])
                    ->sortable(),
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
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\SelectFilter::make('agent')
                    ->relationship('agent.user', 'name'),
                Tables\Filters\Filter::make('paid')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('paid_at')),
                Tables\Filters\Filter::make('unpaid')
                    ->query(fn (Builder $query): Builder => $query->whereNull('paid_at')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_as_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'paid',
                            'paid_at' => now(),
                        ]);
                    }),
                Tables\Actions\Action::make('calculate')
                    ->label('Recalculate')
                    ->icon('heroicon-o-calculator')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->action(function ($record) {
                        // Get the invoice amount
                        $invoiceAmount = $record->invoice?->total_amount ?? 0;

                        // Get agent commission rates
                        $fixed = $record->fixed_amount;
                        $percentage = $record->percentage;

                        // Calculate total commission
                        $total = $fixed + ($percentage / 100 * $invoiceAmount);

                        // Update the commission record
                        $record->update([
                            'total_commission' => $total,
                        ]);
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
            RelationManagers\InvoiceRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCommissions::route('/'),
            'create' => Pages\CreateCommission::route('/create'),
            'edit' => Pages\EditCommission::route('/{record}/edit'),
        ];
    }
}
