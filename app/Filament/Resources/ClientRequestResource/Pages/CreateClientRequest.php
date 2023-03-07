<?php

namespace App\Filament\Resources\ClientRequestResource\Pages;

use App\Filament\Resources\ClientRequestResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateClientRequest extends CreateRecord
{
    protected static string $resource = ClientRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return ClientRequestResource::getUrl('index');
    }
}
