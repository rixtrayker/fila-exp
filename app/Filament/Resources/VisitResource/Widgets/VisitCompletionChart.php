<?php

namespace App\Filament\Resources\VisitResource\Widgets;

use App\Models\Visit;
use App\Helpers\DateHelper;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class VisitCompletionChart extends ChartWidget
{
    protected static ?string $heading = 'Daily Planned Visits Completion';
    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $today = DateHelper::today();
        $mineUsers = User::getMine()->pluck('id');

        // Single query to get all counts at once
        $visitStats = Visit::whereIn('user_id', $mineUsers)
            ->where('visit_date', $today)
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
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
