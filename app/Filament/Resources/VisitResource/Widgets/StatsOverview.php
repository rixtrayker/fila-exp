<?php

namespace App\Filament\Resources\VisitResource\Widgets;

use App\Services\Stats\VisitStatsService;
use App\Services\Stats\OrderStatsService;
use App\Services\Stats\ClientStatsService;
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

    public function __construct()
    {
        $this->visitStatsService = new VisitStatsService();
        $this->orderStatsService = new OrderStatsService();
        $this->clientStatsService = new ClientStatsService();
    }

    protected function getCards(): array
    {
        return [
            $this->visitsStats(),
            $this->workStats(),
            $this->coveredClientsStats(),
            $this->directOrdersStats(),
        ];
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
        return Stat::make('Work', '100%')
            ->description('Work')
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->color('success');
    }

    private function coveredClientsStats(): Stat
    {
        $stats = $this->clientStatsService->getCoveredClientsStats();

        return Stat::make('Covered clients', $stats['count'])
            ->description("{$stats['count']} / {$stats['dailyTarget']} ({$stats['percentage']}%)")
            ->descriptionIcon('heroicon-m-arrow-trending-up')
            ->color('success');
    }

    private function directOrdersStats(): Stat
    {
        $stats = $this->orderStatsService->getOrderStats();

        return Stat::make('Direct orders', $stats['label'])
            ->description($stats['descriptionMessage'])
            ->color($stats['color']);
    }
}
