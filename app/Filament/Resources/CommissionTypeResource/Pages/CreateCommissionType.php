<?php

namespace App\Filament\Resources\CommissionTypeResource\Pages;

use App\Filament\Resources\CommissionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Models\CommissionType;

class CreateCommissionType extends CreateRecord
{
    protected static string $resource = CommissionTypeResource::class;
    
    protected function afterCreate(): void
    {
        // If this commission type is set as default, update all others to not be default
        if ($this->record->is_default) {
            CommissionType::where('id', '!=', $this->record->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }
    }
}
