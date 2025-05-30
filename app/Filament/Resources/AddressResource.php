<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AddressResource\Pages;
use App\Models\Address;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AddressResource extends Resource
{
    protected static ?string $model = Address::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'Client Management';
    protected static ?int $navigationSort = 20;
    
    protected static ?string $recordTitleAttribute = 'label';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Client Information')
                    ->description('Select the client this address belongs to')
                    ->schema([
                        Forms\Components\Select::make('client_id')
                            ->label('Client')
                            ->relationship(
                                name: 'client',
                                titleAttribute: 'id',
                                modifyQueryUsing: fn (Builder $query) => $query->with('user')
                            )
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "Client #{$record->id}")
                            ->preload()
                            ->searchable()
                            ->required()
                            ->prefixIcon('heroicon-o-user-circle'),
                            
                        Forms\Components\TextInput::make('label')
                            ->label('Address Label')
                            ->required()
                            ->prefixIcon('heroicon-o-tag')
                            ->maxLength(255)
                            ->placeholder('Home, Office, etc.')
                            ->helperText('A label to identify this address'),
                    ])
                    ->columns(2)
                    ->collapsible(false),
                
                Forms\Components\Section::make('Address Details')
                    ->description('Physical location information')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('address_line_1')
                                    ->label('Address Line 1')
                                    ->prefixIcon('heroicon-o-map-pin')
                                    ->required()
                                    ->placeholder('Street address, building number')
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('address_line_2')
                                    ->label('Address Line 2')
                                    ->prefixIcon('heroicon-o-map')
                                    ->placeholder('Apartment, suite, unit, etc. (optional)')
                                    ->maxLength(255),
                            ]),
                            
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('city')
                                    ->label('City')
                                    ->prefixIcon('heroicon-o-building-office-2')
                                    ->required()
                                    ->placeholder('City name')
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('state')
                                    ->label('State/Province')
                                    ->prefixIcon('heroicon-o-building-library')
                                    ->required()
                                    ->placeholder('State or province')
                                    ->maxLength(255),
                                    
                                Forms\Components\TextInput::make('postal_code')
                                    ->label('Postal Code')
                                    ->prefixIcon('heroicon-o-envelope')
                                    ->required()
                                    ->placeholder('ZIP or postal code')
                                    ->maxLength(255),
                            ]),
                            
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Checkbox::make('is_default')
                                    ->label('Set as default address')
                                    ->helperText('Only one address can be default for each client')
                                    ->inline(false)
                                    ->afterStateUpdated(function ($state, callable $set, ?Model $record) {
                                        if ($state) {
                                            // If setting as default, no action needed here
                                            // The model will handle unsetting other defaults
                                        }
                                    }),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('client.id')
                    ->label('Client')
                    ->formatStateUsing(function ($state, Address $record) {
                        return $record->client?->user?->name ?? "Client #{$record->client_id}";
                    })
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('label')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('address_line_1')
                    ->label('Address')
                    ->searchable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('city')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('state')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('postal_code')
                    ->searchable(),
                
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean(),
                
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
                Tables\Filters\SelectFilter::make('client_id')
                    ->label('Client')
                    ->relationship(
                        'client', 
                        'id',
                        fn (Builder $query) => $query->with('user')
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->user?->name ?? "Client #{$record->id}")
                    ->searchable()
                    ->preload(),
                    
                Tables\Filters\Filter::make('is_default')
                    ->label('Default Addresses')
                    ->query(fn (Builder $query): Builder => $query->where('is_default', true)),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    
                    Action::make('setDefault')
                        ->label('Set as Default')
                        ->icon('heroicon-o-star')
                        ->hidden(fn (Address $record) => $record->is_default)
                        ->action(function (Address $record) {
                            $record->setAsDefault();
                        }),
                        
                    Tables\Actions\DeleteAction::make(),
                ]),
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
            'index' => Pages\ListAddresses::route('/'),
            'create' => Pages\CreateAddress::route('/create'),
            'edit' => Pages\EditAddress::route('/{record}/edit'),
        ];
    }
}
