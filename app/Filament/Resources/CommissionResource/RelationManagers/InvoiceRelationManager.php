<?php

namespace App\Filament\Resources\CommissionResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoiceRelationManager extends RelationManager
{
    protected static string $relationship = 'invoice';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('total_amount')
                    ->label('Total Amount (RM)')
                    ->required()
                    ->numeric()
                    ->prefix('RM'),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                        'overdue' => 'Overdue',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('due_date')
                    ->required(),
                Forms\Components\DateTimePicker::make('paid_at'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.reference_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.client.user.name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('myr')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'danger' => ['cancelled', 'overdue'],
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
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
                        'overdue' => 'Overdue',
                    ]),
                Tables\Filters\Filter::make('paid')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('paid_at')),
                Tables\Filters\Filter::make('unpaid')
                    ->query(fn (Builder $query): Builder => $query->whereNull('paid_at')),
            ])
            ->headerActions([
                // No header actions as the invoice should be created from the Order
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('view_payments')
                    ->label('View Payments')
                    ->icon('heroicon-o-banknotes')
                    ->url(fn ($record) => route('filament.admin.resources.invoices.edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // No bulk actions needed for this relation
            ]);
    }
}
