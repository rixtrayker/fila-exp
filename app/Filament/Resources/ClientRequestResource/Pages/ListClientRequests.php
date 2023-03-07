<?php

namespace App\Filament\Resources\ClientRequestResource\Pages;

use App\Filament\Resources\ClientRequestResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientRequests extends ListRecords
{
    protected static string $resource = ClientRequestResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
