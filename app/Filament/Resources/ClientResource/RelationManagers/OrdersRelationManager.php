<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Agent;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('agent_id')
                    ->label('Agent')
                    ->options(Agent::query()->pluck('id', 'id'))
                    ->searchable(),
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
                Tables\Columns\TextColumn::make('total_carpets')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('agent.user.name')
                    ->label('Agent')
                    ->searchable()
                    ->toggleable(),
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
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
