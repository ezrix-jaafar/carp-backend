<?php

namespace App\Filament\Resources\CarpetResource\Pages;

use App\Filament\Resources\CarpetResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCarpet extends CreateRecord
{
    protected static string $resource = CarpetResource::class;
    
    protected function beforeCreate(): void
    {
        // Hook into the before create lifecycle event
    }
    
    protected function beforeFill(): void 
    {
        // Disable the qr_code field if it exists
        $this->form->getComponent('qr_code')?->disabled();
    }
    
    protected function afterCreate(): void
    {
        // Get the newly created record
        $carpet = $this->record;
        
        // Get the sequence number for this carpet
        $orderCarpetCount = \App\Models\Carpet::where('order_id', $carpet->order_id)
            ->where('id', '!=', $carpet->id)
            ->count() + 1;
        
        // Generate and update the QR code
        $qrCode = \App\Models\Carpet::generateQrCode(
            $carpet->order_id ?? 0,
            $orderCarpetCount
        );
        
        // Update the carpet with the QR code
        $carpet->update(['qr_code' => $qrCode]);
    }
}
