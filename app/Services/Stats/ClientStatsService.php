<?php

namespace App\Services\Stats;

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
        $coveredClients = $this->visitStatsService->getDailyPlanCoveredPMClients();
        $totalClients = $this->visitStatsService->getPMClientsCount();

        $percentage = round($this->calculatePercentage($coveredClients, $totalClients), 2);

        return [
            'coveredClients' => $coveredClients,
            'totalClients' => $totalClients,
            'percentage' => $percentage,
        ];
    }
}
