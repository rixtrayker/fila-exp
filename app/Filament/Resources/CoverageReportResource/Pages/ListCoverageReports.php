<?php

namespace App\Filament\Resources\CoverageReportResource\Pages;

use App\Filament\Resources\CoverageReportResource;
use App\Models\CoverageReport;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Get the table query using the optimized CoverageReport model with flexible filters
     */
    protected function getTableQuery(): Builder
    {
        $filtersState = $this->getTableFiltersForm()?->getState() ?? [];

        // Option 1: Use the flexible filter normalizer (recommended)
        return CoverageReport::getReportDataWithFilters($filtersState);

        // Option 2: Extract filters manually (if you need custom handling)
        /*
        $dateRange = $filtersState['date_range'] ?? [];
        $fromDate = $dateRange['from_date'] ?? today()->startOfMonth()->toDateString();
        $toDate = $dateRange['to_date'] ?? today()->toDateString();

        $userFilter = $filtersState['user_id'] ?? null;
        if (is_array($userFilter) && array_key_exists('values', $userFilter)) {
            $userFilter = $userFilter['values'];
        }

        $clientType = $filtersState['client_type_id'] ?? null;
        if (is_array($clientType) && array_key_exists('values', $clientType)) {
            $clientType = $clientType['values'];
        }

        $selectedClientTypeId = $clientType ?? \App\Models\ClientType::PM;

        return CoverageReport::getReportData($fromDate, $toDate, [
            'user_id' => $userFilter ?: null,
            'client_type_id' => $selectedClientTypeId,
        ]);
        */

        // Option 3: Use URL extraction (if coming from URL parameters)
        // return CoverageReport::getReportDataFromUrl();

        // Option 4: Use custom method with specific parameters
        /*
        return CoverageReport::getCustomReportData(
            fromDate: $dateRange['from_date'] ?? null,
            toDate: $dateRange['to_date'] ?? null,
            userIds: $userFilter,
            clientTypeId: $selectedClientTypeId
        );
        */
    }
}
