<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'User Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->description('Basic user details and account information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->prefixIcon('heroicon-o-user')
                                    ->placeholder('Enter full name')
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('email')
                                    ->label('Email Address')
                                    ->email()
                                    ->required()
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->placeholder('email@example.com')
                                    ->maxLength(255)
                                    ->unique(ignoreRecord: true),
                            ]),
                    ])
                    ->collapsible(false),
                    
                Forms\Components\Section::make('Access & Role')
                    ->description('Security settings and system access')
                    ->schema([
                        Forms\Components\Grid::make()
                            ->schema([
                                Forms\Components\Select::make('role')
                                    ->label('User Role')
                                    ->required()
                                    ->options([
                                        'client' => 'Client',
                                        'agent' => 'Agent',
                                        'staff' => 'Staff',
                                        'admin' => 'Admin',
                                    ])
                                    ->default('client')
                                    ->native(false)
                                    ->prefixIcon(function (string $state): string {
                                        return match ($state) {
                                            'client' => 'heroicon-o-user',
                                            'agent' => 'heroicon-o-briefcase',
                                            'staff' => 'heroicon-o-wrench-screwdriver',
                                            'admin' => 'heroicon-o-shield-check',
                                            default => 'heroicon-o-user',
                                        };
                                    })
                                    ->helperText('Determines what actions the user can perform'),
                                    
                                Forms\Components\DateTimePicker::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->prefixIcon('heroicon-o-check-badge')
                                    ->hidden(fn (string $operation): bool => $operation === 'create'),
                            ]),
                            
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('password')
                                    ->label('Password')
                                    ->password()
                                    ->required(fn (string $operation): bool => $operation === 'create')
                                    ->dehydrated(fn (?string $state): bool => filled($state))
                                    ->dehydrateStateUsing(fn (string $state): string => bcrypt($state))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-lock-closed')
                                    ->helperText(fn (string $operation): string => $operation === 'create' 
                                        ? 'Required for new users' 
                                        : 'Leave blank to keep current password'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->colors([
                        'primary' => 'client',
                        'success' => 'agent',
                        'warning' => 'staff',
                        'danger' => 'admin',
                    ])
                    ->sortable(),
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
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
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'client' => 'Client',
                        'agent' => 'Agent',
                        'staff' => 'Staff',
                        'admin' => 'Admin',
                    ]),
                Tables\Filters\Filter::make('verified')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('email_verified_at')),
                Tables\Filters\Filter::make('unverified')
                    ->query(fn (Builder $query): Builder => $query->whereNull('email_verified_at')),
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
