<?php

namespace App\Filament\Resources\FrequencyReportResource\Pages;

use App\Filament\Resources\FrequencyReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFrequencyReports extends ListRecords
{
    protected static string $resource = FrequencyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions needed for read-only reports
        ];
    }
}
