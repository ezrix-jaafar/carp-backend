<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StatusHistoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'statusHistories';

    protected static ?string $recordTitleAttribute = 'new_status';

    protected static bool $hasAssociateAction = false;
    protected static bool $hasCreateAction = false;
    protected static bool $hasEditAction = false;
    protected static bool $hasDeleteAction = false;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Status History')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Changed At')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('old_status')
                    ->label('From')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('new_status')
                    ->label('To')
                    ->colors([
                        'primary',
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Changed By')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('note')
                    ->label('Note')
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
