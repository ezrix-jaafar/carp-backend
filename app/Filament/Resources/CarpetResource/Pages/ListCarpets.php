<?php

namespace App\Filament\Resources\CarpetResource\Pages;

use App\Filament\Resources\CarpetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCarpets extends ListRecords
{
    protected static string $resource = CarpetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
