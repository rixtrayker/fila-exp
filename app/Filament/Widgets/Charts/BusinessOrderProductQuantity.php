<?php

namespace App\Filament\Widgets\Charts;

use App\Filament\Resources\SalesReportResource;
use App\Filament\Resources\SalesReportResource\Pages\ListSalesReport;
use App\Models\Company;
use App\Models\Visit;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageTable;
use Livewire\Attributes\On;
use Livewire\Attributes\Reactive;
use Str;

class BusinessOrderProductQuantity  extends ChartWidget
{
    use InteractsWithPageTable;

    public $users;
    public $from;
    public $to;
    public $user_id;
    protected static ?string $maxHeight = '300px';
    private static $data;
    private static $resource = SalesReportResource::class;

    protected function getType(): string
    {
        return 'pie';
    }
    protected function getOptions(): array
    {
        return [
            'scales' => [
                'yAxis' => [
                    'display' => false,
                ],
                'xAxis' => [
                    'display' => false,
                ],
            ],
        ];
    }
    protected function getTablePage(): string
    {
        return ListSalesReport::class;
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


    // public function getColumnSpan(): int|string|array
    // {
    //     return 'full';
    // }
    public function getHeading(): string
    {
        return 'Companies Sales';
    }

    protected function getData(): array
    {
        return [
            'datasets' => $this->getDataSets(),
            'labels' => $this->getLabels(),
        ];
    }
    private function getLabels(): array {
        return $this->getPageTableRecords()->pluck('company_name')->unique()->toArray();
    }

    public function getColumnSpan(): int|string|array
    {
        return 1;
    }

    private function getDataSets()
    {
        $datasets = [
            [
                'label' => 'Companies chart',
                'data'=> $this->getChartData(),
                'backgroundColor' => $this->backgroundColor,
                'borderColor' => $this->borderColor,
            ],
        ];
        return $datasets;
    }

    public function getChartData(): array {
        $data = [];

        foreach(self::getLabels() as $label){
            $data[] = $this->getPageTableRecords()->where('company_name', $label)->sum('quantity');
        }

        return $data;
    }
}
