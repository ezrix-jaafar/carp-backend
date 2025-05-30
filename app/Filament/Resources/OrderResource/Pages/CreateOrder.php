<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;
    
    protected function beforeCreate(): void
    {
        // Get the data from the form
        $data = $this->form->getState();
        
        // Always generate a reference number, regardless of form input
        $this->form->fill(array_merge($data, [
            'reference_number' => \App\Models\Order::generateReferenceNumber()
        ]));
    }
}
