<?php

namespace App\Filament\Resources\AgentResource\Pages;

use App\Filament\Resources\AgentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EditAgent extends EditRecord
{
    protected static string $resource = AgentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $agent = $this->record;
        
        // Add the user data
        if ($agent && $agent->user) {
            $data['name'] = $agent->user->name;
            $data['email'] = $agent->user->email;
            // Don't include password as it's only for setting new passwords
        }
        
        return $data;
    }
    
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return DB::transaction(function () use ($record, $data) {
            // Update agent record
            $record->fixed_commission = $data['fixed_commission'] ?? 0;
            $record->percentage_commission = $data['percentage_commission'] ?? 0;
            $record->status = $data['status'] ?? 'active';
            $record->notes = $data['notes'] ?? null;
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
            
            // Notify the user
            Notification::make()
                ->title('Agent updated')
                ->success()
                ->send();
            
            return $record;
        });
    }
}
