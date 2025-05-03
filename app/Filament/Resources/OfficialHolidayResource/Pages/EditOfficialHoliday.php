<?php

namespace App\Filament\Resources\OfficialHolidayResource\Pages;

use App\Filament\Resources\OfficialHolidayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOfficialHoliday extends EditRecord
{
    protected static string $resource = OfficialHolidayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
