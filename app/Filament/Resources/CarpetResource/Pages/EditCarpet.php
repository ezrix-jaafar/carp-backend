<?php

namespace App\Filament\Resources\CarpetResource\Pages;

use App\Filament\Resources\CarpetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCarpet extends EditRecord
{
    protected static string $resource = CarpetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
