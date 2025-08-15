<?php

namespace App\Filament\Resources\GovernorateResource\Pages;

use App\Filament\Resources\GovernorateResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGovernorate extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = GovernorateResource::class;
}
