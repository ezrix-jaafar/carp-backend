<?php

namespace App\Filament\Resources\AddressResource\Pages;

use App\Filament\Resources\AddressResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Address;

class EditAddress extends EditRecord
{
    protected static string $resource = AddressResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function afterSave(): void
    {
        // If this address was set as default, ensure it's properly set in the model
        if ($this->data['is_default']) {
            $address = Address::find($this->record->id);
            $address->setAsDefault();
        }
    }
}
