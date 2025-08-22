<?php

namespace App\Filament\Resources\VisitResource\Widgets;

use App\Models\Visit;
use App\Helpers\DateHelper;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class VisitCompletionChart extends ChartWidget
{
    protected static ?string $heading = 'Monthly Planned Visits Completion';
    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $today = DateHelper::today();
        $mineUsers = User::getMine()->pluck('id');

        // Single query to get all counts at once
        $visitStats = Visit::whereIn('user_id', $mineUsers)
            ->whereBetween('visit_date', DateHelper::currentMonth()['start'], DateHelper::currentMonth()['end'])
            ->whereNotNull('plan_id')
            ->selectRaw('
                COUNT(*) as total_planned_visits,
                SUM(CASE WHEN status = "visited" THEN 1 ELSE 0 END) as completed_planned_visits,
                SUM(CASE WHEN status IN ("pending", "planned") THEN 1 ELSE 0 END) as pending_planned_visits
            ')
            ->first();

        $totalPlannedVisits = $visitStats->total_planned_visits;
        $completedPlannedVisits = $visitStats->completed_planned_visits;
        $pendingPlannedVisits = $visitStats->pending_planned_visits;

        if ($totalPlannedVisits === 0) {
            $data = [1];
            $colors = ['#FFEB3B'];
            $labels = ['No Planned Visits Today'];
        } else {
            $data = [$completedPlannedVisits, $pendingPlannedVisits];
            $colors = ['#90EE90', '#F08080'];
            $labels = ['Completed', 'Pending'];
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
