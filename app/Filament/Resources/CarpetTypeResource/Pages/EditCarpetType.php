<?php

namespace App\Filament\Resources\CarpetTypeResource\Pages;

use App\Filament\Resources\CarpetTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCarpetType extends EditRecord
{
    protected static string $resource = CarpetTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
