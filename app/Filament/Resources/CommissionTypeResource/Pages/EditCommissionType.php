<?php

namespace App\Filament\Resources\CommissionTypeResource\Pages;

use App\Filament\Resources\CommissionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\CommissionType;

class EditCommissionType extends EditRecord
{
    protected static string $resource = CommissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            Actions\ForceDeleteAction::make(),
            Actions\RestoreAction::make(),
        ];
    }
    
    protected function afterSave(): void
    {
        // If this commission type is set as default, update all others to not be default
        if ($this->record->is_default) {
            CommissionType::where('id', '!=', $this->record->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }
    }
}
