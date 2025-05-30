<?php

namespace App\Filament\Resources\AddonServiceResource\Pages;

use App\Filament\Resources\AddonServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAddonService extends EditRecord
{
    protected static string $resource = AddonServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
