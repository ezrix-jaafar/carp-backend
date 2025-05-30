<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    
    protected static ?string $navigationGroup = 'Client Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Personal Information Section
                Forms\Components\Section::make('Personal Information')
                    ->description('Basic contact details of the client')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label('Full Name')
                                    ->required()
                                    ->placeholder('Enter client\'s full name')
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
                                    ->placeholder('client@example.com')
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
                    
                // Address Section
                Forms\Components\Section::make('Address Information')
                    ->description('Client\'s primary location - will be saved as default in address book')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('address_label')
                                    ->label('Address Label')
                                    ->default('Home')
                                    ->required()
                                    ->prefixIcon('heroicon-o-tag')
                                    ->placeholder('Home, Office, etc.')
                                    ->maxLength(255),
                            ]),
                            
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('address')
                                    ->label('Street Address')
                                    ->required()
                                    ->placeholder('Street address, building name, etc.')
                                    ->rows(2),
                                    
                                Forms\Components\TextInput::make('address_line_2')
                                    ->label('Unit/Suite/Apt')
                                    ->placeholder('Apt #, Suite #, Unit #, etc. (optional)')
                                    ->maxLength(255),
                            ]),
                            
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('city')
                                    ->label('City')
                                    ->required()
                                    ->placeholder('City/Town')
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('state')
                                    ->label('State/Province')
                                    ->required()
                                    ->placeholder('State/Province')
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('postal_code')
                                    ->label('Postal/ZIP Code')
                                    ->required()
                                    ->placeholder('e.g., 50000')
                                    ->maxLength(20),
                            ]),
                    ])
                    ->collapsible(),
                
                // Notes Section (optional)
                Forms\Components\Section::make('Additional Information')
                    ->description('Optional notes and preferences')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->placeholder('Enter any additional notes about this client')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
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
                Tables\Columns\TextColumn::make('phone')
                    ->icon('heroicon-o-phone')
                    ->searchable(),
                Tables\Columns\TextColumn::make('address')
                    ->limit(30)
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                Tables\Columns\TextColumn::make('state')
                    ->searchable(),
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
                Tables\Filters\SelectFilter::make('state')
                    ->attribute('state'),
                Tables\Filters\SelectFilter::make('city')
                    ->attribute('city'),
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
            RelationManagers\AddressesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
