<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AgentResource\Pages;
use App\Filament\Resources\AgentResource\RelationManagers;
use App\Models\Agent;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AgentResource extends Resource
{
    protected static ?string $model = Agent::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';
    
    protected static ?string $navigationGroup = 'User Management';
    
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Personal Information Section
                Forms\Components\Section::make('Personal Information')
                    ->description('Basic contact details of the agent')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->placeholder('Enter agent\'s full name')
                                    ->prefixIcon('heroicon-o-user')
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('phone')
                                    ->label('Phone Number')
                                    ->tel()
                                    ->required()
                                    ->placeholder('e.g., +60123456789')
                                    ->prefixIcon('heroicon-o-phone')
                                    ->maxLength(255),
                            ]),
                            
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->placeholder('agent@example.com')
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('password')
                                    ->label('Account Password')
                                    ->password()
                                    ->required(function () use ($form) {
                                        return $form->getOperation() === 'create';
                                    })
                                    ->dehydrated(function ($state) use ($form) {
                                        return filled($state) || $form->getOperation() === 'create';
                                    })
                                    ->suffixIcon('heroicon-o-lock-closed')
                                    ->maxLength(255)
                                    ->helperText($form->getOperation() === 'edit' ? 'Leave empty to keep current password' : ''),
                            ]),
                    ])
                    ->collapsible(),
                
                // Commission Section
                Forms\Components\Section::make('Commission Structure')
                    ->description('Agent payment and commission details')
                    ->schema([
                        Forms\Components\Card::make()
                            ->schema([
                                Forms\Components\Placeholder::make('commission_info')
                                    ->label('Advanced Commission System')
                                    ->content('Commission types will be configured after the agent is created. You will be able to assign multiple commission types with different rates based on order value ranges.')
                                    ->extraAttributes(['class' => 'text-primary-600']),
                                    
                                // Hidden fields with default values of 0 for database compatibility
                                Forms\Components\Hidden::make('fixed_commission')
                                    ->default(0.00),
                                Forms\Components\Hidden::make('percentage_commission')
                                    ->default(0.00),
                            ])
                            ->columns(1),
                    ])
                    ->collapsible(),
                    
                // Status Section
                Forms\Components\Section::make('Status & Additional Information')
                    ->description('Current agent status and additional notes')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->label('Agent Status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                        'suspended' => 'Suspended',
                                    ])
                                    ->default('active')
                                    ->required()
                                    ->helperText('Controls the agent\'s ability to log in and access the system')
                                    ->prefixIcon(function (string $state): string {
                                        return match ($state) {
                                            'active' => 'heroicon-o-check-circle',
                                            'inactive' => 'heroicon-o-x-circle',
                                            'suspended' => 'heroicon-o-exclamation-circle',
                                            default => 'heroicon-o-question-mark-circle',
                                        };
                                    }),
                            ]),
                            
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Internal Notes')
                                    ->placeholder('Enter any additional information or notes about this agent')
                                    ->helperText('Notes are only visible to administrators')
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
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fixed_commission')
                    ->label('Fixed Commission')
                    ->money('myr')
                    ->sortable(),
                Tables\Columns\TextColumn::make('percentage_commission')
                    ->label('% Commission')
                    ->suffix('%')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'danger' => 'inactive',
                        'warning' => 'suspended',
                    ]),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
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
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ]),
                Tables\Filters\Filter::make('high_commission')
                    ->query(fn (Builder $query): Builder => $query->where('percentage_commission', '>=', 5)),
            ])
            ->actions([
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
            RelationManagers\OrdersRelationManager::class,
            RelationManagers\CommissionsRelationManager::class,
            RelationManagers\CommissionTypesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAgents::route('/'),
            'create' => Pages\CreateAgent::route('/create'),
            'edit' => Pages\EditAgent::route('/{record}/edit'),
        ];
    }
}
