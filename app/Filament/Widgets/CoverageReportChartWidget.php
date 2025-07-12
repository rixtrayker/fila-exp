<?php

namespace App\Filament\Widgets;

use App\Services\Stats\CoverageStatsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class CoverageReportChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Coverage Report - AM';
    protected static ?int $sort = 4;
    
    public ?string $selectedType = 'am';

    public function getDescription(): ?string
    {
        return 'Monthly coverage statistics for ' . strtoupper($this->selectedType) . ' visits';
    }

    protected function getData(): array
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        $type = $this->selectedType;

        $labels = [];
        $completedData = [];
        $pendingData = [];
        $cancelledData = [];

        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $visitData = CoverageStatsService::getVisitData($currentDate, $type);
            
            $labels[] = $currentDate->format('M j');
            $completedData[] = $visitData['data'][0] ?? 0; // Visited
            $pendingData[] = $visitData['data'][1] ?? 0;   // Pending
            $cancelledData[] = $visitData['data'][2] ?? 0; // Missed
            
            $currentDate->addDay();
        }

        // If no data, add sample points to show the chart structure
        if (empty($completedData) || array_sum($completedData) === 0) {
            $labels = ['Jul 1', 'Jul 2', 'Jul 3', 'Jul 4', 'Jul 5'];
            $completedData = [0, 0, 0, 0, 0];
            $pendingData = [0, 0, 0, 0, 0]; 
            $cancelledData = [0, 0, 0, 0, 0];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Completed',
                    'data' => $completedData,
                    'backgroundColor' => '#10B981',
                    'borderColor' => '#10B981',
                ],
                [
                    'label' => 'Pending', 
                    'data' => $pendingData,
                    'backgroundColor' => '#F59E0B',
                    'borderColor' => '#F59E0B',
                ],
                [
                    'label' => 'Cancelled',
                    'data' => $cancelledData,
                    'backgroundColor' => '#EF4444', 
                    'borderColor' => '#EF4444',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
        ];
    }
}