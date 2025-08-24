<?php

namespace App\Filament\Resources\AccountsCoverageReportResource\Pages;

use App\Filament\Resources\AccountsCoverageReportResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListAccountsCoverageReports extends ListRecords
{
    protected static string $resource = AccountsCoverageReportResource::class;

    public function getTableRecordKey(Model $record): string
    {
        return 'id';
    }

    protected function getHeaderActions(): array
    {
        return [
            // No actions needed for read-only reports
        ];
    }
}
