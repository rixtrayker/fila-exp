<?php

namespace App\Filament\Resources\VisitResource\Widgets;

use App\Models\Visit;
use App\Helpers\DateHelper;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class VisitCompletionChartTemp extends ChartWidget
{
    protected static ?string $maxHeight = '250px';
    protected ?array $cachedData = null;

    protected function fetchData(): array
    {
        if ($this->cachedData !== null) {
            return $this->cachedData;
        }

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

        return $this->cachedData = [
            'completed' => $completedVisits,
            'planned' => $plannedVisits,
            'total' => $completedVisits + $plannedVisits,
        ];
    }

    public function getHeading(): string
    {
        $data = $this->fetchData();
        $total = $data['total'];
        $completed = $data['completed'];

        $ratio = $total > 0 ? "({$completed} / {$total})" : '(0 / 0)';

        return "Visit Completion Status Today {$ratio}";
    }

    protected function getData(): array
    {
        $stats = $this->fetchData();
        $totalVisits = $stats['total'];
        $completedVisits = $stats['completed'];
        $plannedVisits = $stats['planned'];

        if ($totalVisits === 0) {
            $data = [1];
            $colors = ['#FFEB3B'];
            $labels = ['No Visits Today'];
        } else {
            $data = [$completedVisits, $plannedVisits];
            $colors = ['rgb(75, 192, 192)', '#F08080'];
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

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'r' => [
                    'display' => false,
                ],
            ],
            'plugins' => [
                 'legend' => [
                     'display' => true
                 ]
            ]
        ];
    }
}
