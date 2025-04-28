<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use DateTime;

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
}