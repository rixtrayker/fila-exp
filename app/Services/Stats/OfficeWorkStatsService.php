<?php

namespace App\Services\Stats;

use App\Models\OfficeWork;
use App\Models\Visit;
use App\Models\VisitType;
class OfficeWorkStatsService
{
    public function getOfficeWorkStats(): array
    {
        $officeWork = OfficeWork::count();
        $visitTypes = VisitType::whereIn('name', ['HealthDay', 'GroupMeeting', 'Conference'])->get();
        $activities = Visit::whereIn('visit_type_id', $visitTypes->pluck('id'))->count();
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
