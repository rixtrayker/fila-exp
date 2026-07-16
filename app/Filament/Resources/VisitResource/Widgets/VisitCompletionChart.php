<?php

namespace App\Filament\Resources\VisitResource\Widgets;

use App\Models\Client;
use App\Models\ClientType;
use App\Models\Scopes\GetMineScope;
use App\Models\Visit;
use Filament\Widgets\ChartWidget;

class VisitCompletionChart extends ChartWidget
{
    protected static ?string $heading = 'Monthly Covered PM Accounts';

    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $visitStats = $this->getStatsNumber();
        $totalVisits = $visitStats['total'];
        $visitedVisits = $visitStats['visited'];
        $unvisitedVisits = $totalVisits - $visitedVisits;

        if ($totalVisits === 0) {
            $data = [1];
            $colors = ['#FFEB3B'];
            $labels = ['No PM Accounts Visited This Month'];
        } else {
            $data = [$visitedVisits, $unvisitedVisits];
            $colors = ['#90EE90', '#F08080'];
            $labels = ['Visited', 'Unvisited'];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Visits',
                    'data' => $data,
                    'backgroundColor' => $colors,
                    'hoverBackgroundColor' => $colors,
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                ],
            ],
            'labels' => $labels,
        ];
    }

    public function getStatsNumber(): array
    {
        $clientsIDs = collect(GetMineScope::getUserIds())
            ->flatMap(fn (int $userId) => Client::query()
                ->accountablePool($userId, ClientType::PM)
                ->where('client_type_id', ClientType::PM)
                ->pluck('id'))
            ->unique()
            ->values();
        $from = today()->startOfMonth()->format('Y-m-d');
        $to = today()->endOfMonth()->format('Y-m-d');
        $coveredClientsIDs = Visit::query()
            ->withinAccountablePool()
            ->whereIn('client_id', $clientsIDs)
            ->where('status', 'visited')
            ->whereBetween('visit_date', [$from, $to])
            ->pluck('client_id')
            ->unique();
        $total = $clientsIDs->count();

        return [
            'total' => $total,
            'visited' => $coveredClientsIDs->count(),
            'completion_rate' => $total > 0 ? $coveredClientsIDs->count() / $total * 100 : 0,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 20,
                        'usePointStyle' => true,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
                'tooltip' => [
                    'enabled' => true,
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
            'scales' => [
                'x' => [
                    'display' => false,
                ],
                'y' => [
                    'display' => false,
                ],
            ],
            'elements' => [
                'arc' => [
                    'borderWidth' => 2,
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => false,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
