<?php

namespace App\Filament\Resources\CoverageReportResource\Pages;

use App\Filament\Resources\CoverageReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCoverageReports extends ListRecords
{
    protected static string $resource = CoverageReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions needed for read-only reports
        ];
    }
}
