<?php

namespace App\Filament\Resources\CityResource\Pages;

use App\Filament\Resources\CityResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCity extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = CityResource::class;

    protected function getRedirectUrl(): string
    {
        return CityResource::getUrl('index');
    }
}
