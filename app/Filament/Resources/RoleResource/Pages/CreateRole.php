<?php

namespace App\Filament\Resources\RoleResource\Pages;

use App\Filament\Resources\RoleResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = RoleResource::class;

    protected function getRedirectUrl(): string
    {
        return RoleResource::getUrl('index');
    }
}
