<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestOrdersWidget extends BaseWidget
{
    protected static ?int $sort = 6;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.client.user.name')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'gray',
                        'assigned' => 'blue',
                        'picked_up' => 'yellow',
                        'in_cleaning' => 'orange',
                        'hq_inspection' => 'purple',
                        'cleaned' => 'lime',
                        'delivered' => 'teal',
                        'completed' => 'green',
                        'cancelled' => 'red',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('total_carpets')
                    ->label('Carpets')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->heading('Latest Orders')
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Order $record): string => route('filament.admin.resources.orders.edit', $record)),
            ]);
    }
}
