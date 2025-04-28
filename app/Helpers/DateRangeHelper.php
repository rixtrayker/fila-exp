<?php

namespace App\Helpers;

use Carbon\Carbon;

class DateRangeHelper
{
    public static function getDateRange()
    {
        $dateRange = request()->get('tableFilters')['date_range'] ?? [];
        $startDate = $dateRange['from_date'] ?? today()->startOfMonth();
        $endDate = $dateRange['to_date'] ?? today()->endOfMonth();

        return [
            Carbon::parse($startDate),
            Carbon::parse($endDate),
        ];
    }
}
