<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaxSettingResource\Pages;
use App\Filament\Resources\TaxSettingResource\RelationManagers;
use App\Models\TaxSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TaxSettingResource extends Resource
{
    protected static ?string $model = TaxSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-receipt-percent';
    protected static ?string $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Tax Settings';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tax Information')
                    ->description('Configure tax settings for invoices')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Tax Name')
                            ->required()
                            ->placeholder('e.g., Sales Tax, VAT, GST')
                            ->maxLength(255),
                            
                        Forms\Components\Select::make('type')
                            ->label('Tax Type')
                            ->options([
                                'percentage' => 'Percentage (%)',
                                'fixed' => 'Fixed Amount',
                            ])
                            ->default('percentage')
                            ->required()
                            ->reactive(),
                            
                        Forms\Components\TextInput::make('rate')
                            ->label(function (callable $get) {
                                return $get('type') === 'percentage' ? 'Tax Rate (%)' : 'Fixed Amount';
                            })
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->step(0.01)
                            ->suffix(function (callable $get) {
                                return $get('type') === 'percentage' ? '%' : '';
                            }),
                            
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Only active tax settings will be applied to invoices')
                            ->default(true)
                            ->required(),
                    ])->columns(2),
                    
                Forms\Components\Section::make('Additional Information')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->placeholder('Enter any additional details about this tax setting')
                            ->rows(3),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Tax Name')
                    ->searchable()
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('type')
                    ->label('Tax Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'percentage' => 'success',
                        'fixed' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'percentage' => 'Percentage',
                        'fixed' => 'Fixed Amount',
                        default => $state,
                    })
                    ->searchable(),
                    
                Tables\Columns\TextColumn::make('rate')
                    ->label('Rate/Amount')
                    ->numeric()
                    ->formatStateUsing(fn (string $state, TaxSetting $record): string => 
                        $record->type === 'percentage' ? $state . '%' : number_format($state, 2)
                    )
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                    
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(30)
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
                //
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
            'index' => Pages\ListTaxSettings::route('/'),
            'create' => Pages\CreateTaxSetting::route('/create'),
            'edit' => Pages\EditTaxSetting::route('/{record}/edit'),
        ];
    }
}
