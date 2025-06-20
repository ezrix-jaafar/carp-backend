<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CarpetResource\Pages;
use App\Filament\Resources\CarpetResource\RelationManagers;
use App\Models\Carpet;
use App\Models\CarpetType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class CarpetResource extends Resource
{
    protected static ?string $model = Carpet::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationGroup = 'Operations';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Order Information Section
                Forms\Components\Section::make('Order Information')
                    ->description('Order details and carpet identification')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('order_id')
                                    ->label('Order Reference')
                                    ->relationship('order', 'reference_number')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->prefixIcon('heroicon-o-shopping-bag')
                                    ->helperText('Select the order this carpet belongs to')
                                    ->createOptionForm([
                                        Forms\Components\Select::make('client_id')
                                            ->label('Client')
                                            ->relationship('client', 'id')
                                            ->getOptionLabelFromRecordUsing(fn ($record) =>
                                                optional($record->user)->name ?: "Client #{$record->id}")
                                            ->searchable()
                                            ->required()
                                            ->prefixIcon('heroicon-o-user'),

                                        Forms\Components\TextInput::make('total_carpets')
                                            ->label('Number of Carpets')
                                            ->required()
                                            ->numeric()
                                            ->default(1)
                                            ->prefixIcon('heroicon-o-squares-2x2'),

                                        Forms\Components\DatePicker::make('pickup_date')
                                            ->label('Pickup Date')
                                            ->required()
                                            ->default(now())
                                            ->prefixIcon('heroicon-o-calendar'),

                                        Forms\Components\TextInput::make('pickup_address')
                                            ->label('Pickup Address')
                                            ->required()
                                            ->prefixIcon('heroicon-o-map-pin')
                                            ->maxLength(255),
                                    ]),

                                Forms\Components\TextInput::make('qr_code')
                                    ->label('QR Code')
                                    ->helperText('Will be auto-generated upon submission')
                                    ->prefixIcon('heroicon-o-qr-code')
                                    ->placeholder('Auto-generated')
                                    ->dehydrated(false)
                                    ->disabled(),
                            ]),
                    ])
                    ->collapsible(),

                // Carpet Details Section
                Forms\Components\Section::make('Carpet Details')
                    ->description('Carpet type, dimensions, and appearance')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Select::make('carpet_type_id')
                                    ->label('Carpet Type')
                                    ->relationship('carpetType', 'name')
                                    ->prefixIcon('heroicon-o-square-3-stack-3d')
                                    ->helperText('Select the type of carpet which determines pricing')
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Type Name')
                                            ->required()
                                            ->prefixIcon('heroicon-o-tag')
                                            ->maxLength(255),

                                        Forms\Components\Textarea::make('description')
                                            ->label('Description')
                                            ->maxLength(65535),

                                        Forms\Components\Toggle::make('is_per_square_foot')
                                            ->label('Price Per Square Foot')
                                            ->default(false)
                                            ->helperText('If enabled, price is calculated per square foot. Otherwise, it\'s a fixed price.'),

                                        Forms\Components\TextInput::make('price')
                                            ->label('Price')
                                            ->required()
                                            ->numeric()
                                            ->prefixIcon('heroicon-o-currency-dollar')
                                            ->prefix('RM')
                                            ->minValue(0),
                                    ])
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->afterStateUpdated(function ($state, callable $set, ?Carpet $record) {
                                        if (!$state) return;

                                        $carpetType = CarpetType::find($state);
                                        if (!$carpetType) return;

                                        // Optionally clear dimensions if switching between pricing types
                                        if ($record && $record->carpetType &&
                                            $record->carpetType->is_per_square_foot != $carpetType->is_per_square_foot) {
                                            $set('width', null);
                                            $set('length', null);
                                        }
                                    }),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('width')
                                    ->label('Width (ft)')
                                    ->numeric()
                                    ->step(0.1)
                                    ->minValue(0.1)
                                    ->required()
                                    ->placeholder('2.5')
                                    ->prefixIcon('heroicon-o-arrows-right-left')
                                    ->helperText('Enter dimensions in feet')
                                    ->reactive(),

                                Forms\Components\TextInput::make('length')
                                    ->label('Length (ft)')
                                    ->numeric()
                                    ->step(0.1)
                                    ->minValue(0.1)
                                    ->required()
                                    ->placeholder('4.0')
                                    ->prefixIcon('heroicon-o-arrows-up-down')
                                    ->helperText('Enter dimensions in feet')
                                    ->reactive(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Placeholder::make('calculated_price')
                                    ->label('Calculated Price')
                                    ->content(function ($get) {
                                        $carpetTypeId = $get('carpet_type_id');
                                        $width = $get('width');
                                        $length = $get('length');

                                        if (!$carpetTypeId || !$width || !$length) {
                                            return 'RM 0.00';
                                        }

                                        $carpetType = CarpetType::find($carpetTypeId);
                                        if (!$carpetType) {
                                            return 'RM 0.00';
                                        }

                                        $price = $carpetType->calculatePrice($width, $length);
                                        return 'RM ' . number_format($price, 2);
                                    }),

                                Forms\Components\TextInput::make('color')
                                    ->label('Carpet Color')
                                    ->required()
                                    ->prefixIcon('heroicon-o-swatch')
                                    ->placeholder('e.g., Beige, Navy Blue')
                                    ->helperText('Main color of the carpet')
                                    ->maxLength(255),
                            ]),

                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('additional_charges')
                                    ->label('Additional Charges')
                                    ->helperText('For extra services like stain removal, repairs, etc.')
                                    ->numeric()
                                    ->prefix('RM')
                                    ->prefixIcon('heroicon-o-plus-circle')
                                    ->placeholder('0.00')
                                    ->default(0.00),
                            ]),
                    ])
                    ->collapsible(),

                // Status and Notes Section
                Forms\Components\Section::make('Status and Notes')
                    ->description('Current processing status and special instructions')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([

                                Forms\Components\Select::make('status')
                                    ->label('Carpet Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'picked_up' => 'Picked Up',
                                        'in_cleaning' => 'In Cleaning',
                                        'cleaned' => 'Cleaned',
                                        'delivered' => 'Delivered',
                                    ])
                                    ->required()
                                    ->default('pending')
                                    ->prefixIcon(function (string $state): string {
                                        return match ($state) {
                                            'pending' => 'heroicon-o-clock',
                                            'picked_up' => 'heroicon-o-truck',
                                            'in_cleaning' => 'heroicon-o-beaker',
                                            'cleaned' => 'heroicon-o-check-circle',
                                            'delivered' => 'heroicon-o-check-badge',
                                            default => 'heroicon-o-question-mark-circle',
                                        };
                                    }),
                            ]),

                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Textarea::make('notes')
                                    ->label('Special Instructions')
                                    ->placeholder('Add any special instructions or observations about this carpet')
                                    ->helperText('Include details about stains, damage, or special care requirements')
                                    ->rows(3),
                            ]),
                    ])
                    ->collapsible(),
            ])
            ; // QR code will be handled in the CreateCarpet page class
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail_url')
                    ->label('Photo')
                    ->height(60),
                Tables\Columns\TextColumn::make('order.reference_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('order.client.user.name')
                    ->label('Client')
                    ->searchable(),
                Tables\Columns\TextColumn::make('qr_code')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('carpetType.name')
                    ->label('Carpet Type')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('square_footage')
                    ->label('Size (sq ft)')
                    ->getStateUsing(fn (Carpet $record) => $record->square_footage ? $record->square_footage . ' sq ft' : null)
                    ->description(fn (Carpet $record) => $record->width && $record->length ?
                        $record->width . ' × ' . $record->length . ' ft' : null),
                // Legacy fields removed as part of carpet type pricing migration
                Tables\Columns\TextColumn::make('color'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'primary' => 'pending',
                        'warning' => ['picked_up', 'in_cleaning'],
                        'success' => ['cleaned', 'delivered'],
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pending',
                        'picked_up' => 'Picked Up',
                        'in_cleaning' => 'In Cleaning',
                        'cleaned' => 'Cleaned',
                        'delivered' => 'Delivered',
                        default => ucfirst($state),
                    }),
                Tables\Columns\TextColumn::make('additional_charges')
                    ->money('myr')
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
                        'picked_up' => 'Picked Up',
                        'in_cleaning' => 'In Cleaning',
                        'cleaned' => 'Cleaned',
                        'delivered' => 'Delivered',
                    ]),
                Tables\Filters\SelectFilter::make('carpet_type_id')
                    ->label('Carpet Type')
                    ->relationship('carpetType', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('order')
                    ->relationship('order', 'reference_number')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('generate_qr')
                    ->label('Generate QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->color('success')
                    ->action(function ($record) {
                        // Generate a QR code for this carpet
                        $timestamp = now()->format('YmdHis');
                        $random = substr(md5(rand()), 0, 5);
                        $qrCode = "CARP-{$record->order_id}-{$timestamp}-{$random}";

                        $record->update(['qr_code' => $qrCode]);
                    }),
                Tables\Actions\Action::make('print_label')
                    ->label('Print Label')
                    ->icon('heroicon-o-printer')
                    ->url(fn ($record) => route('admin.carpets.print-label', ['carpet' => $record->id]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'picked_up' => 'Picked Up',
                                'in_cleaning' => 'In Cleaning',
                                'cleaned' => 'Cleaned',
                                'delivered' => 'Delivered',
                            ])
                            ->required()
                            ->default(fn ($record) => $record->status),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['status' => $data['status']]);

                        // Check if we need to update the order status
                        $allCarpetsStatus = $record->order->carpets()
                            ->pluck('status')
                            ->toArray();

                        // If all carpets have the same status, update the order
                        if (count(array_unique($allCarpetsStatus)) === 1) {
                            $orderStatus = match ($data['status']) {
                                'pending' => 'pending',
                                'picked_up' => 'picked_up',
                                'in_cleaning' => 'in_cleaning',
                                'cleaned' => 'cleaned',
                                'delivered' => 'delivered',
                                default => null,
                            };

                            if ($orderStatus) {
                                $record->order->update(['status' => $orderStatus]);
                            }
                        }
                    }),
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
            RelationManagers\OrderRelationManager::class,
            RelationManagers\AddonServicesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCarpets::route('/'),
            'create' => Pages\CreateCarpet::route('/create'),
            'edit' => Pages\EditCarpet::route('/{record}/edit'),
        ];
    }
}
