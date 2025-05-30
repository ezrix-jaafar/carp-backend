<?php

namespace App\Filament\Resources\ClientResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Address;

class AddressesRelationManager extends RelationManager
{
    protected static string $relationship = 'addresses';

    protected static ?string $recordTitleAttribute = 'label';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('label')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('Home, Office, etc.')
                    ->helperText('A label to identify this address'),
                    
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
                    
                Forms\Components\Checkbox::make('is_default')
                    ->label('Set as default address')
                    ->helperText('Only one address can be default for each client'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('label')
            ->columns([
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
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make()
                        ->after(function (Address $record, array $data) {
                            // If this address was set as default, ensure it's properly set in the model
                            if ($data['is_default']) {
                                $record->setAsDefault();
                            }
                        }),
                        
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
}
