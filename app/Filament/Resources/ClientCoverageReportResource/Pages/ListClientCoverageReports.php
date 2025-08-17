<?php

namespace App\Filament\Resources\ClientCoverageReportResource\Pages;

use App\Filament\Resources\ClientCoverageReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClientCoverageReports extends ListRecords
{
    protected static string $resource = ClientCoverageReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions needed for read-only reports
        ];
    }
}
