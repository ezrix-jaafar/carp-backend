<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoice';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    protected static bool $hasAssociateAction = false;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->helperText('Auto-generated if left empty')
                    ->disabled(fn (string $operation): bool => $operation === 'edit')
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
                    ->required()
                    ->default('pending'),
                Forms\Components\DatePicker::make('issued_at')
                    ->label('Issue Date')
                    ->required()
                    ->default(now()),
                Forms\Components\DatePicker::make('due_date')
                    ->label('Due Date')
                    ->required()
                    ->default(now()->addDays(14)),
                Forms\Components\Textarea::make('notes')
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->searchable()
                    ->sortable(),
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
                Tables\Columns\TextColumn::make('issued_at')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('due_date')
                    ->date()
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
                Tables\Filters\Filter::make('overdue')
                    ->query(fn (Builder $query): Builder => $query->where('due_date', '<', now())->where('status', 'pending')),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(function ($livewire) {
                        $order = $livewire->getOwnerRecord();
                        return $order->status === 'hq_inspection' 
                            ? 'Create Invoice (HQ Inspection)' 
                            : 'Create Invoice';
                    })
                    ->modalHeading(function ($livewire) {
                        $order = $livewire->getOwnerRecord();
                        return $order->status === 'hq_inspection'
                            ? 'Create Invoice During HQ Inspection'
                            : 'Create Invoice';
                    })
                    ->successNotificationTitle(function ($livewire) {
                        $order = $livewire->getOwnerRecord();
                        return $order->status === 'hq_inspection'
                            ? 'Invoice created during HQ inspection'
                            : 'Invoice created successfully';
                    }),
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
                        ]);
                    }),
                Tables\Actions\Action::make('view_payments')
                    ->label('View Payments')
                    ->icon('heroicon-o-credit-card')
                    ->url(fn ($record) => '/admin/payments?invoice_id=' . $record->id)
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('generate_pdf')
                    ->label('Generate PDF')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => route('admin.invoices.pdf', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
