<?php

namespace App\Filament\Resources\BrickResource\Pages;

use App\Filament\Resources\BrickResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBrick extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = BrickResource::class;
}
