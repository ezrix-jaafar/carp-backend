<?php

namespace App\Filament\Resources\AgentResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Client;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('client_id')
                    ->label('Client')
                    ->relationship('client', 'id')
                    ->getOptionLabelFromRecordUsing(fn (Client $record) => 
                        optional($record->user)->name ?: "Client #{$record->id}")
                    ->searchable()
                    ->required(),
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
                Forms\Components\DatePicker::make('pickup_date')
                    ->required(),
                Forms\Components\Textarea::make('pickup_address')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\DatePicker::make('delivery_date'),
                Forms\Components\Textarea::make('delivery_address')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('total_carpets')
                    ->numeric()
                    ->required()
                    ->default(1)
                    ->minValue(1),
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
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'assigned' => 'warning',
                        'picked_up' => 'info',
                        'in_cleaning' => 'info',
                        'cleaned' => 'success',
                        'delivered' => 'success',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
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
                    ->numeric()
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
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
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
                    ->action(function (array $data, $record): void {
                        $record->update([
                            'status' => $data['status'],
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
