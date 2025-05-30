<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use App\Models\User;
use App\Models\Agent;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class CreateAgent extends CreateRecord
{
    protected static string $resource = AgentResource::class;
    
    // Override the create method to handle User creation first
    protected function handleRecordCreation(array $data): Model
    {
        // Use a database transaction to ensure both User and Agent are created together
        return DB::transaction(function () use ($data) {
            // Extract user data
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => bcrypt($data['password']),
                'role' => 'agent',
            ];
            
            // Create the user
            $user = User::create($userData);
            
            // Create the agent linked to the user
            $agent = Agent::create([
                'user_id' => $user->id,
                'fixed_commission' => $data['fixed_commission'] ?? 0,
                'percentage_commission' => $data['percentage_commission'] ?? 0,
                'status' => $data['status'] ?? 'active',
                'notes' => $data['notes'] ?? null,
            ]);
            
            return $agent;
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
            ->title('Agent created')
            ->body('The agent has been created successfully with a new user account.');
    }
}
