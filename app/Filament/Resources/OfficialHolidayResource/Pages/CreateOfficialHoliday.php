<?php

namespace App\Filament\Resources\OfficialHolidayResource\Pages;

use App\Filament\Resources\OfficialHolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOfficialHoliday extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = OfficialHolidayResource::class;
}
