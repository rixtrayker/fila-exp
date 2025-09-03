<?php

namespace App\Services;

use App\Models\AccountsCoverageReport;
use Illuminate\Support\Collection;

class AccountsCoverageReportService
{
    /**
     * Get report data for the Report class (returns Collection)
     */
    public static function getReportData(?array $filters = []): Collection
    {
        return AccountsCoverageReport::getReportData($filters);
    }
}
