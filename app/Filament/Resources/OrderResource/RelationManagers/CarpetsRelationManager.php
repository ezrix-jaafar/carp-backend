<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CarpetsRelationManager extends RelationManager
{
    protected static string $relationship = 'carpets';
    protected static ?string $recordTitleAttribute = 'qr_code';

    // Make sure the relationship key is set
    protected static ?string $inverseRelationship = 'order';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Main carpet information section
                Forms\Components\Section::make('Carpet Information')
                    ->description('Basic details about the carpet')
                    ->schema([
                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('qr_code')
                                    ->label('QR Code')
                                    ->prefixIcon('heroicon-o-qr-code')
                                    ->maxLength(255)
                                    ->helperText('Will be auto-generated upon submission (e.g., CARP-001-20250504)')
                                    ->dehydrated(false)
                                    ->disabled(),
                            ]),

                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\TextInput::make('pack_number')
                                    ->label('Pack Number')
                                    ->prefixIcon('heroicon-o-document-text')
                                    ->helperText('Will be generated as "X/Y" format')
                                    ->dehydrated(false)
                                    ->disabled(),
                            ]),

                        Forms\Components\Grid::make(1)
                            ->schema([
                                Forms\Components\Select::make('carpet_type_id')
                                    ->label('Carpet Type')
                                    ->relationship('carpetType', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('width')
                                    ->label('Width (ft)')
                                    ->numeric()
                                    ->required(false)
                                    ->minValue(0.1)
                                    ->step(0.1)
                                    ->placeholder('Will be measured at HQ')
                                    ->helperText('Enter dimension in feet (Optional for Agents)'),
                                Forms\Components\TextInput::make('length')
                                    ->label('Length (ft)')
                                    ->numeric()
                                    ->required(false)
                                    ->minValue(0.1)
                                    ->step(0.1)
                                    ->placeholder('Will be measured at HQ')
                                    ->helperText('Enter dimension in feet (Optional for Agents)'),
                            ]),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('color')
                                    ->required(false)
                                    ->placeholder('Will be filled at HQ')
                                    ->default('Unknown')
                                    ->dehydrated(true)
                                    ->maxLength(255),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'picked_up' => 'Picked Up',
                                        'in_cleaning' => 'In Cleaning',
                                        'hq_inspection' => 'HQ Inspection',
                                        'cleaned' => 'Cleaned',
                                        'delivered' => 'Delivered',
                                    ])
                                    ->required()
                                    ->default('pending'),
                            ]),
                    ])
                    ->collapsible(),

                // Services and pricing section
                Forms\Components\Section::make('Services & Pricing')
                    ->description('Additional services and charges')
                    ->schema([
                        Forms\Components\TextInput::make('additional_charges')
                            ->label('Additional Charges (RM)')
                            ->numeric()
                            ->prefix('RM')
                            ->default(0.00)
                            ->placeholder('Will be determined at HQ')
                            ->helperText('Optional for Agents, will be determined at HQ'),

                        Forms\Components\Select::make('addon_services')
                            ->label('Addon Services')
                            ->multiple()
                            ->relationship('addonServices', 'name')
                            ->preload()
                            ->searchable()
                            ->helperText('Select any additional services requested by the client')
                            ->required(false),
                    ])
                    ->collapsible(),

                // Documentation section
                Forms\Components\Section::make('Documentation')
                    ->description('Photos and notes')
                    ->schema([
                        Forms\Components\FileUpload::make('carpet_images')
                            ->label('Carpet Photos')
                            ->image()
                            ->multiple()
                            ->maxFiles(5)
                            ->disk('public')
                            ->directory('carpet-images')
                            ->required(false)
                            ->helperText('Take photos of the carpet (required for agents)')
                            ->downloadable()
                            ->dehydrated(false),

                        Forms\Components\Textarea::make('notes')
                            ->label('Client Special Request')
                            ->placeholder('Enter any special requests from the client')
                            ->helperText('Optional - Enter any special cleaning instructions or client notes')
                            ->maxLength(65535),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('qr_code')
            ->columns([
                Tables\Columns\TextColumn::make('qr_code')
                    ->searchable()
                    ->limit(20)
                    ->tooltip(fn ($record): string => $record->qr_code),
                Tables\Columns\TextColumn::make('pack_number')
                    ->label('Pack Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('carpetType.name')
                    ->label('Type')
                    ->searchable(),
                Tables\Columns\TextColumn::make('color')
                    ->searchable(),
                Tables\Columns\TextColumn::make('dimensions')
                    ->label('Dimensions')
                    ->formatStateUsing(function ($state, $record) {
                        return "{$record->width}ft × {$record->length}ft";
                    }),
                Tables\Columns\TextColumn::make('square_footage')
                    ->label('Area (sq ft)')
                    ->formatStateUsing(function ($state, $record) {
                        return number_format($record->width * $record->length, 2);
                    }),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'pending',
                        'info' => ['picked_up', 'in_cleaning'],
                        'success' => ['cleaned', 'delivered'],
                    ]),
                Tables\Columns\TextColumn::make('additional_charges')
                    ->money('myr')
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
                        'pending' => 'Pending',
                        'picked_up' => 'Picked Up',
                        'in_cleaning' => 'In Cleaning',
                        'cleaned' => 'Cleaned',
                        'delivered' => 'Delivered',
                    ]),
                Tables\Filters\SelectFilter::make('carpet_type_id')
                    ->label('Carpet Type')
                    ->relationship('carpetType', 'name'),
            ])
            ->headerActions([
                // Add bulk generate action before create action
                Tables\Actions\Action::make('generateLabels')
                    ->label('Generate Labels in Bulk')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('quantity')
                            ->label('Number of Carpets')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->placeholder('Enter quantity of carpets to generate')
                            ->default(1),

                        Forms\Components\Select::make('carpet_type_id')
                            ->label('Default Carpet Type (Optional)')
                            ->relationship('carpetType', 'name')
                            ->searchable()
                            ->preload()
                            ->required(false)
                            ->helperText('Leave blank if carpet type will be determined during pickup'),

                        Forms\Components\Checkbox::make('generate_pdf')
                            ->label('Generate PDF Labels')
                            ->default(true),
                    ])
                    ->action(function (array $data, Tables\Actions\Action $action): void {
                        $order = $this->getOwnerRecord();
                        $quantity = (int) $data['quantity'];
                        $carpetType = $data['carpet_type_id'];
                        $generatePdf = $data['generate_pdf'] ?? true;

                        // Get current number of carpets
                        $currentCount = $order->carpets()->count();

                        // Create multiple carpet records
                        $carpets = [];
                        for ($i = 1; $i <= $quantity; $i++) {
                            $carpetNumber = $currentCount + $i;
                            $qrCode = \App\Models\Carpet::generateQrCode($order->id, $carpetNumber);
                            $packNumber = $carpetNumber . '/' . ($currentCount + $quantity);

                            $carpet = $order->carpets()->create([
                                'qr_code' => $qrCode,
                                'pack_number' => $packNumber,
                                'carpet_type_id' => $carpetType,
                                'color' => 'Unknown',
                                'status' => 'pending',
                                'additional_charges' => 0,
                            ]);

                            $carpets[] = $carpet;
                        }

                        // Update the order total_carpets field
                        $order->update([
                            'total_carpets' => $currentCount + $quantity
                        ]);

                        // Return success notification
                        if ($generatePdf) {
                            // Redirect to PDF labels for the entire order (50mm x 80mm format)
                            $url = route('admin.orders.print-carpet-labels', ['order' => $order->id]);

                            // Success message with redirect
                            $action->success('Generated ' . $quantity . ' carpet labels successfully!');
                            redirect()->to($url);
                        } else {
                            $action->success('Generated ' . $quantity . ' carpet records successfully!');
                        }
                    }),

                Tables\Actions\CreateAction::make()
                    ->before(function (Tables\Actions\CreateAction $action) {
                        // Nothing needed here, relationship handling is automatic
                    })
                    ->action(function (array $data): void {
                        $order = $this->getOwnerRecord();

                        // Get the total number of carpets for the order (existing + new one)
                        $totalCarpets = $order->carpets()->count() + 1;

                        // Calculate what number this carpet is in the order
                        $carpetNumber = $totalCarpets;

                        // Use the order's total_carpets field or the current carpet number
                        $totalInOrder = $order->total_carpets ?? $carpetNumber;

                        // Generate QR code and pack number
                        $qrCode = \App\Models\Carpet::generateQrCode($order->id, $carpetNumber);
                        $packNumber = $carpetNumber . '/' . $totalInOrder;

                        // Ensure the color field has a default value
                        $color = !empty($data['color']) ? $data['color'] : 'Unknown';

                        // Create the carpet with the generated QR code
                        $carpet = $order->carpets()->create([
                            'qr_code' => $qrCode,
                            'pack_number' => $packNumber,
                            'carpet_type_id' => $data['carpet_type_id'],
                            'width' => $data['width'] ?? null,
                            'length' => $data['length'] ?? null,
                            'color' => $color,
                            'status' => $data['status'] ?? 'pending',
                            'notes' => $data['notes'] ?? null,
                            'additional_charges' => $data['additional_charges'] ?? 0,
                        ]);

                        // Handle addon services
                        if (isset($data['addon_services']) && !empty($data['addon_services'])) {
                            $addonServiceIds = $data['addon_services'];
                            foreach ($addonServiceIds as $addonServiceId) {
                                $carpet->addonServices()->attach($addonServiceId);
                            }
                        }

                        // Handle image uploads
                        if (isset($data['carpet_images']) && !empty($data['carpet_images'])) {
                            foreach ($data['carpet_images'] as $imageFile) {
                                // Store the image
                                $storagePath = 'carpet-images/' . $order->id . '/' . $carpet->id;
                                $path = $imageFile->store($storagePath, 'public');

                                // Create image record associated with this carpet
                                $carpet->images()->create([
                                    'path' => $path,
                                    'file_name' => $imageFile->getClientOriginalName(),
                                    // We don't need to worry about the uploader ID in admin context
                                    'uploaded_by' => null,
                                ]);
                            }
                        }
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('update_status')
                    ->label('Update Status')
                    ->icon('heroicon-o-arrow-path')
                    ->form([
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'picked_up' => 'Picked Up',
                                'hq_inspection' => 'HQ Inspection',
                                'in_cleaning' => 'In Cleaning',
                                'cleaned' => 'Cleaned',
                                'delivered' => 'Delivered',
                                'canceled' => 'Canceled',
                            ])
                            ->required(),
                    ])
                    ->action(function (array $data, $record): void {
                        $record->update([
                            'status' => $data['status'],
                        ]);
                    }),
                Tables\Actions\Action::make('generate_qr')
                    ->label('Generate QR Code')
                    ->icon('heroicon-o-qr-code')
                    ->url(fn ($record) => route('admin.carpets.qr-code', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('update_status_bulk')
                        ->label('Update Status')
                        ->icon('heroicon-o-arrow-path')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->options([
                                    'pending' => 'Pending',
                                    'picked_up' => 'Picked Up',
                                    'hq_inspection' => 'HQ Inspection',
                                    'in_cleaning' => 'In Cleaning',
                                    'cleaned' => 'Cleaned',
                                    'delivered' => 'Delivered',
                                    'canceled' => 'Canceled',
                                ])
                                ->required(),
                        ])
                        ->action(function (array $data, $records): void {
                            $records->each(function ($record) use ($data): void {
                                $record->update(['status' => $data['status']]);
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
