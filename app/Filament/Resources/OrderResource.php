<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Address;
use App\Models\TaxSetting;
use App\Services\InvoiceCalculatorService;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';
    
    protected static ?string $navigationGroup = 'Operations';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer and Assignment')
                    ->description('Client and agent information')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->prefixIcon('heroicon-o-user-group')
                            ->relationship(
                                'client',
                                'id',
                                modifyQueryUsing: fn (Builder $query) => $query->with('user')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                $record->user?->name ?? "Client #{$record->id}")
                            ->searchable()
                            ->optionsLimit(50)
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('agent_id')
                            ->label('Assigned Agent')
                            ->prefixIcon('heroicon-o-user')
                            ->helperText('Optional - can be assigned later')
                            ->relationship(
                                'agent',
                                'id',
                                modifyQueryUsing: fn (Builder $query) => $query->with('user')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                $record->user?->name ?? "Agent #{$record->id}")
                            ->searchable()
                            ->optionsLimit(50)
                            ->preload(),
                    ])
                    ->columns(2)
                    ->collapsible(),
                    
                Forms\Components\Section::make('Order Status')
                    ->description('Order identification and current status')
                    ->schema([
                        Forms\Components\TextInput::make('reference_number')
                            ->label('Order Number')
                            ->prefixIcon('heroicon-o-document-text')
                            ->dehydrated(false)
                            ->disabled()
                            ->default(function () {
                                return Order::generateReferenceNumber();
                            })
                            ->helperText('Will be auto-generated upon submission')
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'awaiting_agent' => 'Awaiting Agent',
                                'assigned' => 'Assigned',
                                'picked_up' => 'Picked Up',
                                'hq_inspection' => 'HQ Inspection',
                                'in_cleaning' => 'In Cleaning',
                                'cleaned' => 'Cleaned',
                                'delivered' => 'Delivered',
                                'completed' => 'Completed',
                                'canceled' => 'Canceled',
                            ])
                            ->default('pending')
                            ->required()
                            ->prefixIcon(function (string $state): string {
                                return match ($state) {
                                    'pending' => 'heroicon-o-clock',
                                    'assigned' => 'heroicon-o-user-plus',
                                    'picked_up' => 'heroicon-o-truck',
                                    'hq_inspection' => 'heroicon-o-eye',
                                    'in_cleaning' => 'heroicon-o-beaker',
                                    'cleaned' => 'heroicon-o-sparkles',
                                    'delivered' => 'heroicon-o-check-badge',
                                    'completed' => 'heroicon-o-check-circle',
                                    'cancelled' => 'heroicon-o-x-circle',
                                    default => 'heroicon-o-question-mark-circle',
                                };
                            }),
                    ])
                    ->columns(2)
                    ->collapsible(),
                    
                Forms\Components\Section::make('Pickup Details')
                    ->description('Information about carpet collection')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\DatePicker::make('pickup_date')
                                    ->label('Pickup Date')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->helperText('Optional - can be scheduled later'),
                            ]),
                            
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Select::make('pickup_address_id')
                                    ->label('Select Pickup Address')
                                    ->prefixIcon('heroicon-o-map-pin')
                                    ->relationship(
                                        'pickupAddress',
                                        'label',
                                        function (Builder $query, callable $get) {
                                            $clientId = $get('client_id');
                                            if ($clientId) {
                                                return $query->where('client_id', $clientId);
                                            }
                                            return $query;
                                        }
                                    )
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->label}: {$record->address_line_1}, {$record->city}")
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\Hidden::make('client_id')
                                            ->dehydrateStateUsing(fn (callable $get) => $get('../../client_id')),
                                        Forms\Components\TextInput::make('label')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Home, Office, etc.'),
                                        Forms\Components\TextInput::make('address_line_1')
                                            ->label('Address Line 1')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('address_line_2')
                                            ->label('Address Line 2')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('city')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('state')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('postal_code')
                                            ->required()
                                            ->maxLength(255),
                                    ]),
                            ]),
                            
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Pickup Instructions')
                                    ->placeholder('Parking information, access codes, etc.')
                                    ->required(false)
                                    ->rows(2),
                            ]),
                    ])
                    ->collapsible(),
                    
                Forms\Components\Section::make('Delivery Details')
                    ->description('Information about carpet return')
                    ->schema([
                        Forms\Components\Checkbox::make('use_same_as_pickup')
                            ->label('Use same address and contact as pickup')
                            ->helperText('Check this if carpets will be returned to the same location')
                            ->live()
                            ->afterStateUpdated(function (Forms\Set $set, $state, callable $get) {
                                if ($state) {
                                    $set('delivery_address_id', $get('pickup_address_id'));
                                    // Could also copy contact information if needed
                                }
                            })
                            ->columnSpanFull(),
                            
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\DatePicker::make('delivery_date')
                                    ->label('Delivery Date')
                                    ->prefixIcon('heroicon-o-calendar')
                                    ->helperText('When the carpets will be returned'),
                            ]),
                            
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Select::make('delivery_address_id')
                                    ->label('Select Delivery Address')
                                    ->prefixIcon('heroicon-o-map-pin')
                                    ->relationship(
                                        'deliveryAddress',
                                        'label',
                                        function (Builder $query, callable $get) {
                                            $clientId = $get('client_id');
                                            if ($clientId) {
                                                return $query->where('client_id', $clientId);
                                            }
                                            return $query;
                                        }
                                    )
                                    ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->label}: {$record->address_line_1}, {$record->city}")
                                    ->searchable()
                                    ->preload()
                                    ->createOptionForm([
                                        Forms\Components\Hidden::make('client_id')
                                            ->dehydrateStateUsing(fn (callable $get) => $get('../../client_id')),
                                        Forms\Components\TextInput::make('label')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Home, Office, etc.'),
                                        Forms\Components\TextInput::make('address_line_1')
                                            ->label('Address Line 1')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('address_line_2')
                                            ->label('Address Line 2')
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('city')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('state')
                                            ->required()
                                            ->maxLength(255),
                                        Forms\Components\TextInput::make('postal_code')
                                            ->required()
                                            ->maxLength(255),
                                    ]),
                            ]),
                    ])
                    ->collapsible(),
                    
                Forms\Components\Section::make('Additional Information')
                    ->description('Order details and notes')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('total_carpets')
                                    ->label('Number of Carpets')
                                    ->prefixIcon('heroicon-o-squares-plus')
                                    ->required()
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1)
                                    ->helperText('Expected number of carpets to be collected'),
                            ]),
                            
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Order Notes')
                                    ->placeholder('Any additional information about this order')
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
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.user.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agent.user.name')
                    ->label('Agent')
                    ->searchable()
                    ->sortable()
                    ->placeholder('Unassigned'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'warning' => ['awaiting_agent', 'assigned'],
                        'info' => ['picked_up', 'in_cleaning'],
                        'success' => ['cleaned', 'delivered', 'completed'],
                        'danger' => 'cancelled',
                        'primary' => 'hq_inspection'
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'awaiting_agent' => 'Awaiting Agent',
                        'assigned' => 'Assigned',
                        'picked_up' => 'Picked Up',
                        'in_cleaning' => 'In Cleaning',
                        'cleaned' => 'Cleaned',
                        'delivered' => 'Delivered',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        default => ucfirst($state),
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('pickup_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_carpets')
                    ->label('Carpets')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('invoice')
                    ->label('Has Invoice')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->invoice !== null)
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
                        'awaiting_agent' => 'Awaiting Agent',
                        'assigned' => 'Assigned',
                        'picked_up' => 'Picked Up',
                        'in_cleaning' => 'In Cleaning',
                        'cleaned' => 'Cleaned',
                        'delivered' => 'Delivered',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('has_invoice')
                    ->query(fn (Builder $query): Builder => $query->has('invoice')),
                Tables\Filters\Filter::make('has_agent')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('agent_id')),
                Tables\Filters\Filter::make('no_agent')
                    ->query(fn (Builder $query): Builder => $query->whereNull('agent_id')),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->icon('heroicon-o-pencil-square'),
                        
                    Tables\Actions\Action::make('assign_agent')
                        ->label('Assign Agent')
                        ->icon('heroicon-o-user-plus')
                        ->form([
                            Forms\Components\Select::make('agent_id')
                                ->label('Agent')
                                ->relationship(
                                    'agent',
                                    'id',
                                    modifyQueryUsing: fn (Builder $query) => $query->with('user')
                                )
                                ->getOptionLabelFromRecordUsing(fn ($record) => 
                                    $record->user?->name ?? "Agent #{$record->id}")
                                ->searchable()
                                ->preload()
                                ->optionsLimit(50)
                                ->required(),
                            Forms\Components\Textarea::make('assignment_notes')
                                ->label('Assignment Notes')
                                ->placeholder('Add any notes for the agent about this order')
                                ->rows(3),
                        ])
                        ->action(function (array $data, $record): void {
                            $record->update([
                                'agent_id' => $data['agent_id'],
                                'status' => 'awaiting_agent',
                                'notes' => $data['assignment_notes'] ? ($record->notes ? $record->notes . "\n\n" : '') . "Assignment notes: {$data['assignment_notes']}" : $record->notes,
                            ]);
                            
                            // Add notification here if needed
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Agent Assigned')
                                ->body('The order has been assigned to the agent and is pending their acceptance.')
                                ->send();
                        })
                        ->visible(fn ($record) => in_array($record->status, ['pending', 'cancelled'])),
                        
                    Tables\Actions\Action::make('accept_order')
                        ->label('Accept Order')
                        ->icon('heroicon-o-check')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Accept Order Assignment')
                        ->modalDescription('By accepting this order, you confirm that you will handle all the carpet cleaning tasks as specified.')
                        ->modalSubmitActionLabel('Accept Order')
                        ->action(function ($record): void {
                            $record->update([
                                'status' => 'assigned',
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Order Accepted')
                                ->body('You have successfully accepted this order.')
                                ->send();
                        })
                        ->visible(fn ($record) => $record->status === 'awaiting_agent'),
                        
                    Tables\Actions\Action::make('reject_order')
                        ->label('Reject Order')
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->form([
                            Forms\Components\Textarea::make('rejection_reason')
                                ->label('Rejection Reason')
                                ->placeholder('Please explain why you cannot accept this order')
                                ->required()
                                ->rows(3),
                        ])
                        ->action(function (array $data, $record): void {
                            // Update the order notes with the rejection reason
                            $record->update([
                                'agent_id' => null,  // Remove agent assignment
                                'status' => 'pending',  // Set back to pending
                                'notes' => ($record->notes ? $record->notes . "\n\n" : '') . "Order rejected by agent. Reason: {$data['rejection_reason']}",
                            ]);
                            
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Order Rejected')
                                ->body('You have rejected this order and it has been returned to the pending queue.')
                                ->send();
                        })
                        ->visible(fn ($record) => $record->status === 'awaiting_agent'),
                        
                    Tables\Actions\Action::make('update_status')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Pending',
                                    'assigned' => 'Assigned',
                                    'picked_up' => 'Picked Up',
                                    'hq_inspection' => 'HQ Inspection',
                                    'in_cleaning' => 'In Cleaning',
                                    'cleaned' => 'Cleaned',
                                    'delivered' => 'Delivered',
                                    'completed' => 'Completed',
                                    'canceled' => 'Canceled',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $record): void {
                            $record->update([
                                'status' => $data['status'],
                            ]);
                        }),
                        
                    Tables\Actions\Action::make('print_carpet_labels')
                        ->label('Print Carpet Labels')
                        ->icon('heroicon-o-printer')
                        ->color('success')
                        ->url(fn ($record) => route('admin.orders.print-carpet-labels', ['order' => $record->id]))
                        ->openUrlInNewTab()
                        ->visible(fn ($record) => $record->carpets()->count() > 0),
                    
                    Tables\Actions\Action::make('generate_invoice')
                        ->label('Generate Invoice')
                        ->icon('heroicon-o-receipt-percent')
                        ->color('warning')
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
                                ->placeholder('No tax'),
                                
                            Forms\Components\Grid::make(2)
                                ->schema([
                                    Forms\Components\TextInput::make('discount')
                                        ->label('Discount')
                                        ->numeric()
                                        ->minValue(0)
                                        ->default(0)
                                        ->step(0.01),
                                        
                                    Forms\Components\Select::make('discount_type')
                                        ->label('Discount Type')
                                        ->options([
                                            'fixed' => 'Fixed Amount',
                                            'percentage' => 'Percentage (%)',
                                        ])
                                        ->default('fixed'),
                                ]),
                                
                            Forms\Components\DatePicker::make('due_date')
                                ->label('Due Date')
                                ->default(now()->addDays(14))
                                ->required(),
                                
                            Forms\Components\Textarea::make('notes')
                                ->label('Invoice Notes')
                                ->placeholder('Enter any additional notes to be included on the invoice')
                                ->rows(3),
                        ])
                        ->action(function (array $data, $record): void {
                            // Create the invoice using the service
                            $invoiceCalculator = new InvoiceCalculatorService();
                            $invoice = $invoiceCalculator->generateInvoice($record, $data);
                            
                            // Provide feedback using Filament's Notification facade
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Invoice Generated')
                                ->body('Invoice #' . $invoice->invoice_number . ' created successfully!')
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->modalHeading('Generate Invoice')
                        ->modalDescription('This will calculate the invoice amount based on carpets, their types, dimensions, and addon services. You can add optional discount and taxes.')
                        ->modalSubmitActionLabel('Generate Invoice')
                        // Only allow invoice generation for orders in appropriate status and don't already have an invoice
                        ->visible(function ($record) {
                            // Must have carpets and not already have an invoice
                            if ($record->carpets()->count() === 0 || $record->invoice()->exists()) {
                                return false;
                            }
                            
                            // Allow invoice generation for orders where all carpets are in HQ inspection stage
                            if ($record->status === 'hq_inspection') {
                                return true;
                            }
                            
                            // Otherwise, only allow for cleaned/delivered/completed orders
                            return in_array($record->status, ['cleaned', 'delivered', 'completed']);
                        }),
                ])
                ->icon('heroicon-m-ellipsis-vertical')
                ->label('Actions')
                ->size('sm')
                ->color('gray'),
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
            RelationManagers\CarpetsRelationManager::class,
            RelationManagers\InvoicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
