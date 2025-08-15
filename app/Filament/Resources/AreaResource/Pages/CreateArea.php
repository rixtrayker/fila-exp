<?php

namespace App\Filament\Resources\AreaResource\Pages;

use App\Filament\Resources\AreaResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateArea extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = AreaResource::class;
}
