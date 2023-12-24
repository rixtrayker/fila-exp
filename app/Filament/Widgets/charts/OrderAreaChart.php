<?php

namespace App\Filament\Widgets\Charts;

use App\Filament\Resources\OrderReportResource;
use App\Filament\Resources\OrderReportResource\Pages\ListOrdersReport;
use App\Filament\Resources\SalesReportResource;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Str;

class OrderAreaChart  extends ChartWidget
{
    use InteractsWithPageTable;

    protected static ?string $maxHeight = '300px';
    private static $data;
    private static $resource = OrderReportResource::class;

    protected function getType(): string
    {
        return 'pie';
    }
    protected function getTablePage(): string
    {
        return ListOrdersReport::class;
    }

    protected $backgroundColor = [
        'rgba(75, 192, 192, 0.2)',
        'rgba(54, 162, 235, 0.2)',
        'rgba(255, 99, 132, 0.2)',
        'rgba(153, 102, 255, 0.2)',
        'rgba(201, 203, 207, 0.2)',
        'rgba(255, 205, 86, 0.2)',
        'rgba(255, 159, 64, 0.2)',
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
        return [
            'datasets' => $this->getDataSets(),
            'labels' => $this->getLabels(),
        ];
    }
    private function getLabels(): array {
        return array_values($this->getPageTableRecords()->pluck('area_name')->unique()->toArray());
    }

    public function getColumnSpan(): int|string|array
    {
        return 1;
    }

    private function getDataSets()
    {
        $datasets = [
            [
                'label' => 'Area chart',
                'data'=> $this->getChartData(),
                'backgroundColor' => $this->backgroundColor,
                'borderColor' => $this->borderColor,
            ],
        ];
        return $datasets;
    }

    public function getChartData(): array {
        $data = [];

        foreach($this->getLabels() as $label){
            $data[] = $this->getPageTableRecords()->where('area_name', $label)->sum('total');
        }

        return $data;
    }
}
