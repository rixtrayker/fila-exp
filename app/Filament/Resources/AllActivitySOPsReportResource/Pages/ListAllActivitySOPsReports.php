<?php

namespace App\Filament\Resources\AllActivitySOPsReportResource\Pages;

use App\Filament\Resources\AllActivitySOPsReportResource;
use App\Models\AllActivitySOPsReport;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\Log;

class ListAllActivitySOPsReports extends ListRecords
{
    protected static string $resource = AllActivitySOPsReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions needed for read-only reports
        ];
    }

    /**
     * Override to handle service-computed results instead of database queries
     */
    public function getTableRecords(): EloquentCollection|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Contracts\Pagination\CursorPaginator
    {
        try {
            $filtersState = $this->getTableFiltersForm()?->getState() ?? [];

            $rows = AllActivitySOPsReport::getReportDataWithFilters($filtersState);

            $models = $rows->map(function (array $row) {
                $model = new AllActivitySOPsReport();
                $model->forceFill($row);

                return $model;
            });

            return new EloquentCollection($models->all());
        } catch (\Throwable $e) {
            Log::error('Error in AllActivitySOPsReport getTableRecords: ' . $e->getMessage());

            return new EloquentCollection([]);
        }
    }
}
