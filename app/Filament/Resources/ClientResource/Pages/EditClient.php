<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\Address;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditClient extends EditRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $client = $this->record;
        
        // Add the user data
        if ($client && $client->user) {
            $data['name'] = $client->user->name;
            $data['email'] = $client->user->email;
        }
        
        // If we have a default address, prefill the address fields
        if ($client && $defaultAddress = $client->defaultAddress) {
            $data['address_label'] = $defaultAddress->label;
            $data['address'] = $defaultAddress->address_line_1;
            $data['address_line_2'] = $defaultAddress->address_line_2;
            $data['city'] = $defaultAddress->city ?? $client->city;
            $data['state'] = $defaultAddress->state ?? $client->state;
            $data['postal_code'] = $defaultAddress->postal_code;
        }
        
        return $data;
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // Update client record
            $record->phone = $data['phone'];
            
            // For legacy fields
            $record->city = $data['city'] ?? null;
            $record->state = $data['state'] ?? null;
            $record->address = $data['address'] ?? null;
            
            $record->save();
            
            // Update the user record
            $user = $record->user;
            if ($user) {
                $user->name = $data['name'];
                $user->email = $data['email'];
                
                // Only update password if it was provided
                if (!empty($data['password'])) {
                    $user->password = bcrypt($data['password']);
                }
                
                $user->save();
            }
            
            // Update or create address if address fields were provided
            if ($data['address'] ?? null) {
                $addressData = [
                    'label' => $data['address_label'] ?? 'Home',
                    'address_line_1' => $data['address'],
                    'address_line_2' => $data['address_line_2'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'postal_code' => $data['postal_code'] ?? null,
                ];
                
                // Find existing default address or create a new one
                $address = $record->defaultAddress()->first();
                
                if ($address) {
                    $address->update($addressData);
                } else {
                    $address = Address::create(array_merge($addressData, [
                        'client_id' => $record->id,
                        'is_default' => true,
                    ]));
                }
                
                $address->setAsDefault();
                
                // Notify the user
                Notification::make()
                    ->title('Client and address updated')
                    ->success()
                    ->send();
            }
            
            return $record;
        });
    }
}
