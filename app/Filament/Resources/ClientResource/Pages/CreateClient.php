<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Models\Address;
use App\Models\User;
use App\Models\Client;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateClient extends CreateRecord
{
    protected static string $resource = ClientResource::class;
    
    // Override the create method to handle User creation first
    protected function handleRecordCreation(array $data): Model
    {
        // Use a database transaction to ensure both User and Client are created together
        return DB::transaction(function () use ($data) {
            // Extract user data
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'role' => 'client',
            ];
            
            // Create the user
            $user = User::create($userData);
            
            // Create the client linked to the user
            $client = Client::create([
                'user_id' => $user->id,
                'phone' => $data['phone'],
                'city' => $data['city'] ?? null,
                'state' => $data['state'] ?? null,
                'address' => $data['address'] ?? null,
            ]);
            
            // Create address record if address details are provided
            if ($data['address'] ?? null) {
                $address = Address::create([
                    'client_id' => $client->id,
                    'label' => $data['address_label'] ?? 'Home',
                    'address_line_1' => $data['address'],
                    'address_line_2' => $data['address_line_2'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'postal_code' => $data['postal_code'] ?? null,
                    'is_default' => true,
                ]);
                
                // Set as default (this will handle updating other addresses)
                $address->setAsDefault();
            }
            
            return $client;
        });
    }
    
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    
    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Client created')
            ->body('The client has been created successfully with a new user account and default address.');
    }
}
