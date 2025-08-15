<?php

namespace App\Filament\Resources\ClientRequestResource\Pages;

use App\Filament\Resources\ClientRequestResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateClientRequest extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = ClientRequestResource::class;

    protected function getRedirectUrl(): string
    {
        return ClientRequestResource::getUrl('index');
    }
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();

        return $data;
    }
}
