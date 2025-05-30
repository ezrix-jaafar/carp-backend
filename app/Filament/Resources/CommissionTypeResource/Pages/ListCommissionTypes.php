<?php

namespace App\Filament\Resources\CommissionTypeResource\Pages;

use App\Filament\Resources\CommissionTypeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCommissionTypes extends ListRecords
{
    protected static string $resource = CommissionTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
