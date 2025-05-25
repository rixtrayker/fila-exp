<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use DateTime;
use App\Helpers\DateHelper;
use App\Models\Activity;
use App\Models\OfficeWork;
use App\Models\OfficialHoliday;
use App\Helpers\SortedStringSet;

class VacationCalculator
{
    /**
     * Calculate the total vacation days for a user within a date range
     *
     * @param User $user The user to calculate vacation days for
     * @param Carbon $fromDate Start date of the range
     * @param Carbon $toDate End date of the range
     * @return float Total vacation days in the specified range
     */
    public function calculateTotalVacationDaysInRange(User $user, Carbon $fromDate, Carbon $toDate): float
    {
        $vacationRequests = $this->getVacationRequestsInRange($user, $fromDate, $toDate);
        $totalVacationDays = 0;

        foreach ($vacationRequests as $request) {
            foreach ($request->vacationDurations as $duration) {
                $totalVacationDays += $this->calculateDurationDaysInRange($duration, $fromDate, $toDate);
            }
        }

        return $totalVacationDays;
    }

    /**
     * Get approved vacation requests that intersect with the date range
     *
     * @param User $user The user to get vacation requests for
     * @param Carbon $fromDate Start date of the range
     * @param Carbon $toDate End date of the range
     * @return \Illuminate\Database\Eloquent\Collection Collection of vacation requests
     */
    private function getVacationRequestsInRange(User $user, Carbon $fromDate, Carbon $toDate)
    {
        return $user->vacationRequests()
            ->approved()
            ->whereHas('vacationDurations', function($query) use ($fromDate, $toDate) {
                $query->whereDate('start', '<=', $toDate)
                      ->whereDate('end', '>=', $fromDate);
            })
            ->with('vacationDurations')
            ->get();
    }

    /**
     * Calculate vacation days for a duration that may fully or partially overlap with the date range
     *
     * @param object $duration Vacation duration object
     * @param Carbon $fromDate Start date of the range
     * @param Carbon $toDate End date of the range
     * @return float Number of vacation days in the range
     */
    private function calculateDurationDaysInRange($duration, Carbon $fromDate, Carbon $toDate): float
    {
        // Convert string dates to Carbon if needed
        $durationStart = $duration->start instanceof Carbon ? $duration->start : Carbon::parse($duration->start);
        $durationEnd = $duration->end instanceof Carbon ? $duration->end : Carbon::parse($duration->end);

        // Skip durations that don't overlap with the range
        if ($durationEnd->lt($fromDate) || $durationStart->gt($toDate)) {
            return 0;
        }

        // If duration is completely within the range
        if ($durationStart->gte($fromDate) && $durationEnd->lte($toDate)) {
            return $duration->duration;
        }

        // Calculate overlapping portion
        return $this->calculateOverlappingVacationDays($duration, $fromDate, $toDate);
    }

    /**
     * Calculate vacation days for a duration that partially overlaps with the date range
     *
     * @param object $duration Vacation duration object
     * @param Carbon $fromDate Start date of the range
     * @param Carbon $toDate End date of the range
     * @return float Number of overlapping vacation days
     */
    public function calculateOverlappingVacationDays($duration, Carbon $fromDate, Carbon $toDate): float
    {
        // Convert string dates to Carbon if needed
        $durationStart = $duration->start instanceof Carbon ? $duration->start : Carbon::parse($duration->start);
        $durationEnd = $duration->end instanceof Carbon ? $duration->end : Carbon::parse($duration->end);

        $start = max($durationStart, $fromDate);
        $end = min($durationEnd, $toDate);

        $diffInDays = $this->calculateDaysDifference($start, $end);

        // Calculate days between the overlapping dates
        $days = $diffInDays + 1; // Add 1 because diff excludes the end date

        // Apply shift adjustments based on overlap scenario
        $startDate = $start->format('Y-m-d');
        $endDate = $end->format('Y-m-d');
        $durationStartDate = $durationStart->format('Y-m-d');
        $durationEndDate = $durationEnd->format('Y-m-d');

        $startShift = $startDate === $durationStartDate ? $duration->start_shift : 'AM';
        $endShift = $endDate === $durationEndDate ? $duration->end_shift : 'PM';

        // Adjust for partial days
        if ($days == 1) {
            return $this->calculateShiftAdjustment($startShift, $endShift);
        } else {
            $fullDays = max(0, $days - 2); // Exclude start and end days, but not for 2-day ranges
            $startAdjustment = $startShift === 'AM' ? 1.0 : 0.5; // Full day if AM, half day if PM
            $endAdjustment = $endShift === 'PM' ? 1.0 : 0.5; // Full day if PM, half day if AM

            return $fullDays + $startAdjustment + $endAdjustment;
        }
    }

    /**
     * Calculate the difference in days between two dates
     *
     * @param mixed $start Start date
     * @param mixed $end End date
     * @return int Number of days difference
     */
    private function calculateDaysDifference($start, $end): int
    {
        $startDate = $start instanceof DateTime || $start instanceof Carbon ? $start : new DateTime($start);
        $endDate = $end instanceof DateTime || $end instanceof Carbon ? $end : new DateTime($end);

        return $startDate->diff($endDate)->days;
    }

    /**
     * Calculate shift adjustment based on start and end shifts
     *
     * @param string $startShift AM or PM shift for start
     * @param string $endShift AM or PM shift for end
     * @return float Shift adjustment value (0.5 or 1.0)
     */
    private function calculateShiftAdjustment(string $startShift, string $endShift): float
    {
        if ($startShift === $endShift) {
            return 0.5;
        } elseif ($startShift === 'AM' && $endShift === 'PM') {
            return 1.0;
        }

        return 0.0; // Default case (e.g., PM to AM, which should be rare)
    }

    /**
     * Calculate the total duration of a vacation period including shift considerations
     *
     * @param string|Carbon $start Start date
     * @param string|Carbon $end End date
     * @param string $startShift AM or PM shift for start
     * @param string $endShift AM or PM shift for end
     * @return float Total duration in days
     */
    public function calculateTotalDuration($start, $end, $startShift, $endShift): float
    {
        $diffInDays = $this->calculateDaysDifference($start, $end);
        return $diffInDays + $this->calculateShiftAdjustment($startShift, $endShift);
    }

    public function vacationDatesInRange(User $user, Carbon $startDate, Carbon $endDate): array
    {
        $vacationRequests = $user->vacationRequests()
            ->approved()
            ->whereHas('vacationDurations', function($query) use ($startDate, $endDate) {
                $query->whereBetween('start', [$startDate, $endDate])
                      ->orWhereBetween('end', [$startDate, $endDate]);
            })
            ->with('vacationDurations')
            ->get();

        $vacationDates = [];

        foreach ($vacationRequests as $request) {
            foreach ($request->vacationDurations as $duration) {
                $start = Carbon::parse($duration->start);
                $end = Carbon::parse($duration->end);
                $allDatesinRange = Carbon::parse($start)->daysUntil($end);
                foreach ($allDatesinRange as $date) {
                    $vacationDates[] = $date;
                }
            }
        }

        return $vacationDates;
    }

    public function actualWorkingDaysSet(User $user, Carbon $startDate, Carbon $endDate): SortedStringSet
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        $datesInRange = Carbon::parse($startDate)->daysUntil($endDate);
        $datesSet = SortedStringSet::fromArray($datesInRange->toArray());

        $weekends = DateHelper::getWeekendInRange($startDate, $endDate);
        $weekendsSet = SortedStringSet::fromArray($weekends);

        $vacationDates = $this->vacationDatesInRange($user, $startDate, $endDate);
        $vacationSet = SortedStringSet::fromArray($vacationDates);
        $officialHolidaysSet = OfficialHoliday::getSetOfOfficialHolidaysInRange($startDate, $endDate);

        $offDays = $officialHolidaysSet->union($weekendsSet)->union($vacationSet);


        return $datesSet->difference($offDays);
    }

    public function visitDatesInRangeSet(User $user, Carbon $startDate, Carbon $endDate): SortedStringSet
    {
        $workingDays = $this->actualWorkingDaysSet($user, $startDate, $endDate);
        $activitiesDates = SortedStringSet::fromArray($this->activitiesDatesInRange($user, $startDate, $endDate));
        $officeworkDates = SortedStringSet::fromArray($this->officeworkDatesInRange($user, $startDate, $endDate));

        return $workingDays->difference($activitiesDates)->difference($officeworkDates);
    }

    public function officeworkDatesInRange(User $user, Carbon $startDate, Carbon $endDate): array
    {
        return OfficeWork::query()
            ->where('user_id', $user->id)
            ->whereBetween('time_from', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw('date_format(time_from, "%Y-%m-%d") as date')
            ->get()
            ->pluck('date')
            ->toArray();
    }

    public function activitiesDatesInRange(User $user, Carbon $startDate, Carbon $endDate): array
    {
        return Activity::query()
            ->where('user_id', $user->id)
            ->whereBetween('date', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->selectRaw('date_format(date, "%Y-%m-%d") as date')
            ->get()
            ->pluck('date')
            ->toArray();
    }
}