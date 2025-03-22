<?php

namespace App\Services\Stats;

use App\Helpers\DateHelper;
use App\Models\Client;
use App\Models\Visit;
use App\Traits\StatsHelperTrait;

class ClientStatsService
{
    use StatsHelperTrait;

    private VisitStatsService $visitStatsService;

    public function __construct()
    {
        $this->visitStatsService = app(VisitStatsService::class);
    }

    public function getCoveredClientsStats(): array
    {
        $doneVisitsClients = $this->visitStatsService->getVisits()
            ->where('status', 'visited')
            ->where('visit_date', DateHelper::today())
            ->count();

        $dailyTargetRatio = 0.8;
        $totalClients = Client::count();
        $dailyTarget = $totalClients * $dailyTargetRatio;
        $percentage = $this->calculatePercentage($doneVisitsClients, $dailyTarget);

        return [
            'count' => $doneVisitsClients,
            'percentage' => $percentage,
            'dailyTarget' => $dailyTarget
        ];
    }
}
