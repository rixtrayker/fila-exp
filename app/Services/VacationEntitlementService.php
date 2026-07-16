<?php

namespace App\Services;

use App\Models\User;
use App\Models\VacationDuration;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class VacationEntitlementService
{
    public function __construct(
        private readonly VacationCalculator $calculator,
    ) {
    }

    public function spentAnnualDays(
        User $user,
        int $year,
        int $excludeRequestId = null,
    ): float {
        return $this->spentDaysInRange(
            $user,
            Carbon::create($year, 1, 1)->startOfDay(),
            Carbon::create($year, 12, 31)->endOfDay(),
            true,
            $excludeRequestId,
        );
    }

    public function remainingAnnualDays(
        User $user,
        int $year = null,
        int $excludeRequestId = null,
    ): float {
        $year ??= now()->year;

        return (float) ($user->annual_vacation_days ?? 21)
            - $this->spentAnnualDays($user, $year, $excludeRequestId);
    }

    public function spentDaysInRange(
        User $user,
        Carbon $fromDate,
        Carbon $toDate,
        bool $annualEntitlementOnly = false,
        int $excludeRequestId = null,
    ): float {
        return (float) $this->approvedDurationsQuery(
            $user,
            $fromDate,
            $toDate,
            $annualEntitlementOnly,
            $excludeRequestId,
        )
            ->get()
            ->sum(fn (VacationDuration $duration): float => $this->calculator
                ->calculateDurationDaysInRange($duration, $fromDate, $toDate));
    }

    private function approvedDurationsQuery(
        User $user,
        Carbon $fromDate,
        Carbon $toDate,
        bool $annualEntitlementOnly,
        ?int $excludeRequestId,
    ): Builder {
        return VacationDuration::query()
            ->select('vacation_durations.*')
            ->join(
                'vacation_requests as entitlement_requests',
                'entitlement_requests.id',
                '=',
                'vacation_durations.vacation_request_id',
            )
            ->join(
                'vacation_types as entitlement_types',
                'entitlement_types.id',
                '=',
                'entitlement_requests.vacation_type_id',
            )
            ->where('entitlement_requests.user_id', $user->id)
            ->where('entitlement_requests.approved', '>', 0)
            ->whereDate('vacation_durations.start', '<=', $toDate)
            ->whereDate('vacation_durations.end', '>=', $fromDate)
            ->when(
                $annualEntitlementOnly,
                fn (Builder $query) => $query->where(
                    'entitlement_types.consumes_annual_entitlement',
                    true,
                ),
            )
            ->when(
                $excludeRequestId,
                fn (Builder $query) => $query->where(
                    'entitlement_requests.id',
                    '!=',
                    $excludeRequestId,
                ),
            );
    }
}
