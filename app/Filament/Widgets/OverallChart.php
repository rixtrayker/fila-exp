<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;
use App\Services\Stats\CoverageStatsService;

class OverallChart extends ChartWidget
{
    public string $selectedType = 'am';
    protected static ?string $maxHeight = '300px';

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
                    'display' => true,
                    'position' => 'bottom',
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
        ];
    }

    protected $backgroundColor = [
        'rgba(75, 192, 192, 0.8)',
        'rgba(255, 205, 86, 0.8)',
        'rgba(255, 99, 132, 0.8)',
    ];

    protected $borderColor = [
        'rgb(75, 192, 192)',
        'rgb(255, 205, 86)',
        'rgb(255, 99, 132)',
    ];

    public function getHeading(): string
    {
        return CoverageStatsService::getCoverageHeading($this->selectedType);
    }

    public function getColumnSpan(): int|string|array
    {
        return 1;
    }

        protected function getData(): array
    {
        $data = CoverageStatsService::getCoverageData($this->selectedType);

        return [
            'datasets' => [
                [
                    'label' => 'Visits',
                    'data' => $data['data'],
                    'backgroundColor' => $this->backgroundColor,
                    'borderColor' => $this->borderColor,
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }



    #[On('switchChartType')]
    public function switchType($type): void
    {
        $this->selectedType = $type;
        $this->updateChartData();
    }

    public function updateChartData(): void
    {
        $this->dispatch('updateChartData', data: $this->getData());
    }
}
