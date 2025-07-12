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

        // Get all planned visits (including completed ones)
        $totalPlannedVisits = Visit::whereIn('user_id', $mineUsers)
            ->where('visit_date', $today)
            ->whereNotNull('plan_id') // Only visits that were planned
            ->count();

        // Get completed planned visits
        $completedPlannedVisits = Visit::whereIn('user_id', $mineUsers)
            ->where('visit_date', $today)
            ->whereNotNull('plan_id')
            ->where('status', 'visited')
            ->count();

        // Get pending planned visits
        $pendingPlannedVisits = Visit::whereIn('user_id', $mineUsers)
            ->where('visit_date', $today)
            ->whereNotNull('plan_id')
            ->whereIn('status', ['pending', 'planned'])
            ->count();

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
