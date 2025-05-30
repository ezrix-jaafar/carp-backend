<?php

namespace App\Filament\Resources\AddonServiceResource\Pages;

use App\Filament\Resources\AddonServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAddonServices extends ListRecords
{
    protected static string $resource = AddonServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
