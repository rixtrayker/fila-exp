<?php

namespace App\Services\Stats;

use App\Helpers\DateHelper;
use App\Models\Visit;
use Illuminate\Support\Collection;
use App\Traits\StatsHelperTrait;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Client;
use App\Services\VisitCacheService;
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
            ->select(['visit_date', 'status', 'plan_id', 'client_id'])
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
        $cacheKey = "visits_{$userId}_{$date}";

        $visitCacheService = app(VisitCacheService::class);
        $fullCacheKey = $visitCacheService->makePublicCacheKey('visit_stats_visits', $userId, $date);
        return $visitCacheService->getPublicCached($fullCacheKey, function () {
            return $this->getVisitsQuery()->get();
        }, 1800);
    }

    /**
     * Get daily plan covered clients
     */
    public function getDailyPlanCoveredPMClients(): int
    {
        $userId = Auth::id();
        $date = DateHelper::today()->format('Y-m-d');

        $visitCacheService = app(VisitCacheService::class);
        $fullCacheKey = $visitCacheService->makePublicCacheKey('visit_stats_daily_plan', $userId, $date);
        $clientIds = $visitCacheService->getPublicCached($fullCacheKey, function () {
            return $this->getVisitsQuery()
                ->whereNotNull('plan_id')
                ->select('client_id')
                ->distinct();
        }, 1800);

        $clientIds = $clientIds->pluck('client_id');
        $clients = Client::whereIn('id', $clientIds)->where('client_type_id', 1)->count();
        return $clients;
    }

    /**
     * Get clients count
     */
    public function getPMClientsCount(): int
    {
        $userId = Auth::id();
        $date = DateHelper::today()->format('Y-m-d');

        $visitCacheService = app(VisitCacheService::class);
        $fullCacheKey = $visitCacheService->makePublicCacheKey('visit_stats_pm_clients_count', $userId, $date);
        return $visitCacheService->getPublicCached($fullCacheKey, function () {
            return Client::where('client_type_id', 1)->count();
        }, 1800);
    }

    /**
     * Get achieved visits percentage
     */
    public function getAchievedVisits(): string
    {
        $userId = Auth::id();
        $date = DateHelper::today()->format('Y-m-d');

        $visitCacheService = app(VisitCacheService::class);
        $fullCacheKey = $visitCacheService->makePublicCacheKey('visit_stats_achieved', $userId, $date);
        return $visitCacheService->getPublicCached($fullCacheKey, function () {
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
        }, 1800);
    }

    /**
     * Get planned vs actual visits
     */
    public function getPlannedVsActualVisits(): array
    {
        $userId = Auth::id();
        $date = DateHelper::today()->format('Y-m-d');

        $visitCacheService = app(VisitCacheService::class);
        $fullCacheKey = $visitCacheService->makePublicCacheKey('visit_stats_planned_vs_actual', $userId, $date);
        return $visitCacheService->getPublicCached($fullCacheKey, function () {
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
        }, 1800);
    }

    /**
     * Get done plan visits
     */
    public function getDonePlanVisits(): int
    {
        $userId = Auth::id();
        $date = DateHelper::today()->format('Y-m-d');

        $visitCacheService = app(VisitCacheService::class);
        $fullCacheKey = $visitCacheService->makePublicCacheKey('visit_stats_done_plan', $userId, $date);
        return $visitCacheService->getPublicCached($fullCacheKey, function () {
            return $this->getVisitsQuery()
                ->where('status', 'visited')
                ->count();
        }, 1800);
    }

    /**
     * Get visit stats
     */
    public function getVisitStats(): array
    {
        $userId = Auth::id();
        $date = DateHelper::today()->format('Y-m-d');

        $visitCacheService = app(VisitCacheService::class);
        $fullCacheKey = $visitCacheService->makePublicCacheKey('visit_stats_overview', $userId, $date);
        return $visitCacheService->getPublicCached($fullCacheKey, function () {
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
        }, 1800);
    }
}
