<?php

namespace App\Filament\Resources\VisitResource\Widgets;

use App\Services\Stats\VisitStatsService;
use App\Services\Stats\OrderStatsService;
use App\Services\Stats\ClientStatsService;
use App\Services\Stats\OfficeWorkStatsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Collection;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Client;

class StatsOverview extends BaseWidget
{
    private VisitStatsService $visitStatsService;
    private OrderStatsService $orderStatsService;
    private ClientStatsService $clientStatsService;
    private OfficeWorkStatsService $officeWorkStatsService;
    public function __construct()
    {
        $this->visitStatsService = new VisitStatsService();
        $this->orderStatsService = new OrderStatsService();
        $this->clientStatsService = new ClientStatsService();
        $this->officeWorkStatsService = new OfficeWorkStatsService();
    }

    protected function getCards(): array
    {
        if (auth()->user()->hasRole('medical-rep')) {
            return [
                    $this->visitsStats(),
                    $this->plannedVsActualVisits(),
                    $this->workStats(),
                ];
        }
        return [
            $this->visitsStats(),
            $this->workStats(),
            $this->coveredClientsStats(),
            $this->directOrdersStats(),
        ];
    }

    private function plannedVsActualVisits(): Stat
    {
        $stats = $this->visitStatsService->getPlannedVsActualVisits();
        $plannedVisits = $stats['plannedVisits'];
        $actualVisits = $stats['actualVisits'];
        $percentage = $stats['percentage'];

        $message = match (true) {
            $percentage == 0 => "No data",
            $percentage > 0 && $plannedVisits > 0 && $actualVisits > 0 => $this->calculatePercentage($stats['actualVisits'], $stats['plannedVisits']) . "% ( actual / planned )",
            $plannedVisits > 0 && $actualVisits == 0 => "100% ( planned )",
            $plannedVisits == 0 && $actualVisits > 0 => "100% ( actual )",
            default => "{$plannedVisits} - Actual visits: {$actualVisits}"
        };

        return Stat::make('Planned vs actual visits', $message)
            ->description($message)
            ->color('primary');
    }

    private function visitsStats(): Stat
    {
        $stats = $this->visitStatsService->getVisitStats();

        return Stat::make('Done visits', $this->visitStatsService->getAchievedVisits())
            ->description($stats['descriptionMessage'])
            ->descriptionIcon('heroicon-s-document-text')
            ->color($stats['color']);
    }

    private function workStats(): Stat
    {
        $stats = $this->officeWorkStatsService->getOfficeWorkStats();

        return Stat::make('Total activities', $stats['totalActivities'])
            ->description("Activities {$stats['activities']} - Office work {$stats['officeWork']}")
            ->color($stats['color']);
    }

    private function coveredClientsStats(): Stat
    {
        $stats = $this->clientStatsService->getCoveredClientsStats();

        $coveredClients = $stats['coveredClients'] ?? 0;
        $totalClients = $stats['totalClients'] ?? 0;
        $percentage = $stats['percentage'] ?? 0;
        $uncoveredClients = $totalClients - $coveredClients;

        $message = "{$coveredClients} covered  - {$uncoveredClients} uncovered ({$percentage}%)";

        return Stat::make('Accounts', $totalClients)
            ->description($message)
            ->color('primary');
            // ->descriptionIcon('heroicon-m-arrow-trending-up')
            // ->color('success');
    }

    private function directOrdersStats(): Stat
    {
        $stats = $this->orderStatsService->getOrderStats();

        return Stat::make('Direct orders', $stats['label'])
            ->description($stats['descriptionMessage'])
            ->color($stats['color']);
    }
}
