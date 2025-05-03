<?php

namespace App\Filament\Resources\OfficialHolidayResource\Pages;

use App\Filament\Resources\OfficialHolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfficialHolidays extends ListRecords
{
    protected static string $resource = OfficialHolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
