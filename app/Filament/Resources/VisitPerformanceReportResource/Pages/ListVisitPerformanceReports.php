<?php

namespace App\Filament\Resources\VisitPerformanceReportResource\Pages;

use App\Filament\Resources\VisitPerformanceReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVisitPerformanceReports extends ListRecords
{
    protected static string $resource = VisitPerformanceReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions needed for read-only reports
        ];
    }
}
