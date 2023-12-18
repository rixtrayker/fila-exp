<?php

namespace App\Filament\Resources\ClientRequestTypeResource\Pages;

use App\Filament\Resources\ClientRequestTypeResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageClientRequestTypes extends ManageRecords
{
    protected static string $resource = ClientRequestTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
