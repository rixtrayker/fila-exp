<?php

namespace App\Filament\Resources\VisitResource\Widgets;

use App\Models\Visit;
use App\Helpers\DateHelper;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class VisitCompletionChart extends ChartWidget
{
    protected static ?string $heading = 'Visit Completion Status Today';
    protected static ?string $maxHeight = '250px';

    protected function getData(): array
    {
        $today = DateHelper::today();
        $mineUsers = User::getMine()->pluck('id');

        $plannedVisits = Visit::whereIn('user_id', $mineUsers)
            ->where('visit_date', $today)
            ->whereIn('status', ['pending', 'planned'])
            ->count();

        $completedVisits = Visit::whereIn('user_id', $mineUsers)
            ->where('visit_date', $today)
            ->where('status', 'visited')
            ->count();

        $totalVisits = $completedVisits + $plannedVisits;

        if ($totalVisits === 0) {
            $data = [0];
            $colors = ['#FFEB3B'];
            $labels = ['No Visits Today'];
        } else {
            $data = [$completedVisits, $plannedVisits];
            $colors = ['#90EE90', '#F08080'];
            $labels = ['Completed', 'Planned/Pending'];
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
