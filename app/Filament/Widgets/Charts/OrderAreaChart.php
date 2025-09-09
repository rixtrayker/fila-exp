<?php

namespace App\Filament\Widgets\Charts;

use App\Filament\Resources\OrderReportResource;
use App\Filament\Resources\OrderReportResource\Pages\ListOrdersReport;
use App\Filament\Resources\SalesReportResource;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Illuminate\Support\Str;

class OrderAreaChart extends ChartWidget
{
    use InteractsWithPageTable;

    protected static ?string $maxHeight = '300px';
    protected static ?string $pollingInterval = null;

    protected function getType(): string
    {
        return 'pie';
    }

    protected function getOptions(): array
    {
        return [
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

    protected function getTablePage(): string
    {
        return ListOrdersReport::class;
    }

    protected $backgroundColor = [
        'rgba(75, 192, 192, 0.5)',
        'rgba(54, 162, 235, 0.5)',
        'rgba(255, 99, 132, 0.5)',
        'rgba(153, 102, 255, 0.5)',
        'rgba(201, 203, 207, 0.5)',
        'rgba(255, 205, 86, 0.5)',
        'rgba(255, 159, 64, 0.5)',
    ];

    protected $borderColor = [
        'rgb(75, 192, 192)',
        'rgb(54, 162, 235)',
        'rgb(255, 99, 132)',
        'rgb(153, 102, 255)',
        'rgb(201, 203, 207)',
        'rgb(255, 205, 86)',
        'rgb(255, 159, 64)',
    ];

    public function getHeading(): string
    {
        return 'Area Orders Profit';
    }

    protected function getData(): array
    {
        $labels = $this->getLabels();
        $data = $this->getChartData($labels);

        return [
            'datasets' => [
                [
                    'label' => 'Area Orders',
                    'data' => $data,
                    'backgroundColor' => $this->backgroundColor,
                    'borderColor' => $this->borderColor,
                ],
            ],
            'labels' => $labels,
        ];
    }

    private function getLabels(): array
    {
        $records = $this->getPageTableRecords();

        if ($records->isEmpty()) {
            return ['No Data Available'];
        }

        return array_values($records->pluck('area_name')->filter()->unique()->toArray());
    }

    public function getColumnSpan(): int|string|array
    {
        return 1;
    }

    private function getChartData(array $labels): array
    {
        $records = $this->getPageTableRecords();

        if ($records->isEmpty() || empty($labels) || $labels[0] === 'No Data Available') {
            return [1]; // Return dummy data to prevent chart errors
        }

        $data = [];

        foreach ($labels as $label) {
            $areaTotal = $records->where('area_name', $label)->sum('total');
            $data[] = $areaTotal > 0 ? $areaTotal : 0;
        }

        return $data;
    }
}
