<?php

namespace App\Filament\Resources\VisitResource\Widgets;

use App\Models\Visit;
use App\Helpers\DateHelper;
use Filament\Widgets\ChartWidget;
use App\Models\User;
use Carbon\Carbon;
use App\Models\UserBricksView;
use App\Models\Client;
use App\Models\ClientType;
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
        $bricksIDs = UserBricksView::getUserBrickIds(auth()->user()->id);
        $clientsIDs = Client::where('client_type_id',ClientType::PM)->whereIn('brick_id', $bricksIDs)->pluck('id')->toArray();
        $from = today()->startOfMonth()->format('Y-m-d');
        $to = today()->endOfMonth()->format('Y-m-d');
        $coveredClientsIDs = Visit::whereIn('client_id', $clientsIDs)->where('status', 'visited')->whereBetween('visit_date', [$from, $to])->pluck('client_id')->unique()->toArray();
        $coveredClientsIDs = Client::whereIn('id', $coveredClientsIDs)->where('client_type_id', ClientType::PM)->pluck('id')->toArray();
        $total = count($clientsIDs);

        return [
            'total' => $total,
            'visited' => count($coveredClientsIDs),
            'completion_rate' => $total > 0 ? count($coveredClientsIDs) / $total * 100 : 0,
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
