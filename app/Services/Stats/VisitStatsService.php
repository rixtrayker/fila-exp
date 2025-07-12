<?php

namespace App\Services\Stats;

use App\Helpers\DateHelper;
use App\Models\Visit;
use Illuminate\Support\Collection;
use App\Traits\StatsHelperTrait;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Client;
use App\Services\VisitStatsCacheService;
use Illuminate\Support\Facades\Auth;

class VisitStatsService
{
    use StatsHelperTrait;

    /**
     * Get visits query for the current week
     */
    public function getVisitsQuery(): Builder
    {
        $startOfPlan = DateHelper::getFirstOfWeek();
        $endOfPlan = (clone $startOfPlan)->addDays(7);

        return Visit::query()
            ->select(['visit_date', 'status', 'plan_id'])
            ->whereIn('status', ['visited', 'pending'])
            ->whereDate('visit_date', '>=', $startOfPlan)
            ->whereDate('visit_date', '<=', $endOfPlan);
    }

    /**
     * Get visits with caching
     */
    public function getVisits(): Collection
    {
        $userId = Auth::id();
        $date = DateHelper::today()->format('Y-m-d');

        return VisitStatsCacheService::getCachedVisitStats($userId, $date, function () {
            return $this->getVisitsQuery()->get();
        });
    }

    /**
     * Get daily plan covered clients
     */
    public function getDailyPlanCoveredClients(): int
    {
        $userId = Auth::id();
        $date = DateHelper::today()->format('Y-m-d');

        return VisitStatsCacheService::getCachedVisitStats($userId, $date, function () {
            return $this->getVisitsQuery()
                ->whereNotNull('plan_id')
                ->select('client_id')
                ->distinct()
                ->count();
        });
    }

    /**
     * Get clients count
     */
    public function getClientsCount(): int
    {
        $userId = Auth::id();
        $date = DateHelper::today()->format('Y-m-d');

        return VisitStatsCacheService::getCachedVisitStats($userId, $date, function () {
            return Client::count();
        });
    }

    /**
     * Get achieved visits percentage
     */
    public function getAchievedVisits(): string
    {
        $userId = Auth::id();
        $date = DateHelper::today()->format('Y-m-d');

        return VisitStatsCacheService::getCachedVisitStats($userId, $date, function () {
            $totalVisits = $this->getVisitsQuery()
                ->whereNotNull('plan_id')
                ->where('visit_date', DateHelper::today())
                ->count();

            $planDoneVisits = $this->getVisitsQuery()
                ->whereNotNull('plan_id')
                ->where('visit_date', DateHelper::today())
                ->where('status', 'visited')
                ->count();

            if ($planDoneVisits === 0) {
                return '0 %';
            }

            $percentage = $this->calculatePercentage($planDoneVisits, $totalVisits);
            return "$percentage %";
        });
    }

    /**
     * Get planned vs actual visits
     */
    public function getPlannedVsActualVisits(): array
    {
        $userId = Auth::id();
        $date = DateHelper::today()->format('Y-m-d');

        return VisitStatsCacheService::getCachedVisitStats($userId, $date, function () {
            $plannedVisits = $this->getVisitsQuery()
                ->where('status', 'pending')
                ->whereNotNull('plan_id')
                ->where('visit_date', DateHelper::today())
                ->count();

            $actualVisits = $this->getVisitsQuery()
                ->where('status', 'visited')
                ->where('visit_date', DateHelper::today())
                ->count();

            $percentage = $this->calculatePercentage($actualVisits, $plannedVisits);

            return [
                'plannedVisits' => $plannedVisits,
                'actualVisits' => $actualVisits,
                'percentage' => $percentage
            ];
        });
    }

    /**
     * Get done plan visits
     */
    public function getDonePlanVisits(): int
    {
        $userId = Auth::id();
        $date = DateHelper::today()->format('Y-m-d');

        return VisitStatsCacheService::getCachedVisitStats($userId, $date, function () {
            return $this->getVisitsQuery()
                ->where('status', 'visited')
                ->count();
        });
    }

    /**
     * Get visit stats
     */
    public function getVisitStats(): array
    {
        $userId = Auth::id();
        $date = DateHelper::today()->format('Y-m-d');

        return VisitStatsCacheService::getCachedVisitStats($userId, $date, function () {
            $actualVisits = $this->getVisitsQuery()
                ->where('status', 'visited')
                ->where('visit_date', DateHelper::today())
                ->count();

            $plannedVisits = $this->getVisitsQuery()
                ->where('status', 'pending')
                ->whereNotNull('plan_id')
                ->where('visit_date', DateHelper::today())
                ->count();

            if ($plannedVisits === 0) {
                return [
                    'achievedRatio' => 0,
                    'descriptionMessage' => "No planned visits",
                    'color' => 'info',
                    'actualVisits' => 0,
                    'plannedVisits' => 0
                ];
            }

            $achievedRatio = $this->calculatePercentage($actualVisits, $plannedVisits);

            return [
                'achievedRatio' => $achievedRatio,
                'descriptionMessage' => "$actualVisits / $plannedVisits Done ($achievedRatio % of planned visits done)",
                'color' => $this->getStatsColor($achievedRatio),
                'actualVisits' => $actualVisits,
                'plannedVisits' => $plannedVisits
            ];
        });
    }
}
