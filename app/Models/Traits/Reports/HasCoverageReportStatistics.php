<?php

namespace App\Models\Traits\Reports;

use App\Helpers\DateHelper;
use App\Helpers\DateRangeHelper;
use App\Models\Setting;
use Carbon\Carbon;
use App\Services\VacationCalculator;

trait HasCoverageReportStatistics
{
    public function getAreaNameAttribute()
    {
        return $this->areas()?->first()?->name;
    }

    public function getActualVisitsAttribute()
    {
        [$fromDate, $toDate] = DateRangeHelper::getDateRange();
        return $this->visits()->whereBetween('visit_date', [$fromDate, $toDate])->count();
    }

    public function getVacationsCountAttribute()
    {
        [$fromDate, $toDate] = DateRangeHelper::getDateRange();
        return app(VacationCalculator::class)->calculateTotalVacationDaysInRange($this, $fromDate, $toDate);
    }

    public function getWorkingDaysAttribute()
    {
        [$fromDate, $toDate] = DateRangeHelper::getDateRange();
        return DateHelper::countWorkingDays($fromDate, $toDate);
    }

    public function getActualWorkingDaysAttribute()
    {
        [$fromDate, $toDate] = DateRangeHelper::getDateRange();
        $visitDays = app(VacationCalculator::class)->visitDatesInRangeSet($this, $fromDate, $toDate);
        return $visitDays->count();
    }

    public function getDailyVisitTargetAttribute(): int
    {
        return Setting::getSetting('medical_rep_visit_target')->value ?? 8;
    }


    public function getActivitiesCountAttribute()
    {
        return $this->activities()->count();
    }

    public function getOfficeWorkCountAttribute()
    {
        return $this->officeWorks()->count();
    }

    public function getSopsAttribute(): float
    {
        $dailyTarget = Setting::getSetting('medical_rep_visit_target')->value ?? 8;
        return round($this->actual_visits / ($this->actual_working_days * $dailyTarget) * 100, 2);
    }

    public function getCallRateAttribute()
    {
        $dailyTarget = Setting::getSetting('medical_rep_visit_target')->value ?? 8;
        return round(($this->actual_visits / $this->actual_working_days) * $dailyTarget, 2);
    }
}
