<?php

namespace App\Filament\Resources\CarpetResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderRelationManager extends RelationManager
{
    protected static string $relationship = 'order';

    protected static ?string $recordTitleAttribute = 'reference_number';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Order Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference_number')
                            ->required()
                            ->maxLength(255)
                            ->disabled(),
                        Forms\Components\Select::make('client_id')
                            ->relationship('client', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                optional($record->user)->name ?: "Client #{$record->id}")
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('agent_id')
                            ->relationship('agent', 'id')
                            ->getOptionLabelFromRecordUsing(fn ($record) => 
                                optional($record->user)->name ?: "Agent #{$record->id}")
                            ->searchable()
                            ->nullable(),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('Order Details')
                    ->schema([
                        Forms\Components\TextInput::make('total_carpets')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'assigned' => 'Assigned',
                                'picked_up' => 'Picked Up',
                                'in_cleaning' => 'In Cleaning',
                                'cleaned' => 'Cleaned',
                                'delivered' => 'Delivered',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->required(),
                    ])
                    ->columns(2),
                    
                Forms\Components\Section::make('Pickup & Delivery')
                    ->schema([
                        Forms\Components\DatePicker::make('pickup_date')
                            ->required(),
                        Forms\Components\TextInput::make('pickup_address')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('delivery_date')
                            ->nullable(),
                        Forms\Components\TextInput::make('delivery_address')
                            ->nullable()
                            ->maxLength(255),
                    ])
                    ->columns(2),
                    
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference_number')
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('client.user.name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('agent.user.name')
                    ->label('Agent')
                    ->searchable()
                    ->placeholder('Not Assigned'),
                Tables\Columns\TextColumn::make('total_carpets')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'pending',
                        'secondary' => 'assigned',
                        'warning' => ['picked_up', 'in_cleaning'],
                        'success' => ['cleaned', 'delivered', 'completed'],
                        'danger' => 'cancelled',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'assigned' => 'Assigned',
                        'picked_up' => 'Picked Up',
                        'in_cleaning' => 'In Cleaning',
                        'cleaned' => 'Cleaned',
                        'delivered' => 'Delivered',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('pickup_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('delivery_date')
                    ->date()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'assigned' => 'Assigned',
                        'picked_up' => 'Picked Up',
                        'in_cleaning' => 'In Cleaning',
                        'cleaned' => 'Cleaned',
                        'delivered' => 'Delivered',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                    ]),
                Tables\Filters\Filter::make('has_agent')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('agent_id')),
                Tables\Filters\Filter::make('no_agent')
                    ->query(fn (Builder $query): Builder => $query->whereNull('agent_id')),
            ])
            ->headerActions([
                // We don't need create actions in this relation manager
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('view_all_carpets')
                    ->label('View All Carpets')
                    ->icon('heroicon-o-squares-2x2')
                    ->url(fn ($record) => route('filament.admin.resources.orders.edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                // No bulk actions needed for this relation
            ]);
    }
}
