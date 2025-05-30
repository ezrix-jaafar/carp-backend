<?php

namespace App\Filament\Resources\CarpetTypeResource\Pages;

use App\Filament\Resources\CarpetTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCarpetTypes extends ListRecords
{
    protected static string $resource = CarpetTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
