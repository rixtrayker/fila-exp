<?php

namespace App\Filament\Resources\ClientTypeResource\Pages;

use App\Filament\Resources\ClientTypeResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageClientTypes extends ManageRecords
{
    protected static string $resource = ClientTypeResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
