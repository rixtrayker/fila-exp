<?php

namespace App\Filament\Resources\EditRequestResource\Pages;

use App\Filament\Resources\EditRequestResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateEditRequest extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = EditRequestResource::class;
}
