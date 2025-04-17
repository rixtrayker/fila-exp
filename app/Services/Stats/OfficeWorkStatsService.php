<?php

namespace App\Services\Stats;

use App\Models\Activity;
use App\Models\OfficeWork;
class OfficeWorkStatsService
{
    public function getOfficeWorkStats(): array
    {
        $officeWork = OfficeWork::count();
        $activities = Activity::count();
        $totalActivities = $activities + $officeWork;
        $color = 'success';

        if ($totalActivities === 0) {
            $color = 'secondary';
        }

        return [
            'officeWork' => $officeWork,
            'activities' => $activities,
            'totalActivities' => $totalActivities,
            'color' => $color,
        ];
    }
}
