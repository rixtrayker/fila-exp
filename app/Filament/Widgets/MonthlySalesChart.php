<?php

namespace App\Filament\Widgets;

use App\Models\BusinessOrder;
use App\Models\Company;
use App\Models\Order;
use Filament\Widgets\BarChartWidget;

class MonthlySalesChart extends BarChartWidget
{
    protected static ?string $maxHeight = '300px';
    protected $backgroundColor = [
        'rgba(255, 99, 132, 0.2)',
        'rgba(75, 192, 192, 0.2)',
        'rgba(54, 162, 235, 0.2)',
        'rgba(153, 102, 255, 0.2)',
        'rgba(201, 203, 207, 0.2)',
        'rgba(255, 205, 86, 0.2)',
        'rgba(255, 159, 64, 0.2)',
    ];
    protected $borderColor = [
        'rgb(255, 99, 132)',
        'rgb(75, 192, 192)',
        'rgb(54, 162, 235)',
        'rgb(153, 102, 255)',
        'rgb(201, 203, 207)',
        'rgb(255, 205, 86)',
        'rgb(255, 159, 64)',
      ];


    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }
    public function getHeading(): string
    {
        return 'Monthly Sales';
    }

    protected function getData(): array
    {
        return [
            'datasets' => $this->getDataSets(),
            'labels' => $this->getLabels(),

        ];
    }
    private function getLabels(){

        $data = [];
        foreach ($this->getYearPeriod() as $date)
        {
             $data[] = $date->year.' '.$date->monthName;
        }
        return $data;
    }
    private function getYearPeriod(){
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $period = $startOfMonth->subMonths(12)->monthsUntil($endOfMonth);

        return $period;
    }

    private function getDataSets()
    {
        $datasets = [];
        $companies = Company::all();

        foreach ($companies as $key => $company) {
            $datasets[] = [
                'label' => $company->name,
                'data' => $this->getChartData($company),
                'backgroundColor' => $this->backgroundColor[$key],
                'borderColor' => $this->borderColor[$key],
                'borderWidth' => 1
            ];
        }
        return $datasets;
    }

    private function getChartData($company){

        $data = [];

        $branchesId = $company->branches()->pluck('id')->toArray();

        foreach ($this->getYearPeriod() as $date)
        {
            $data[] = BusinessOrder::query()
            ->whereIn('company_branch_id',$branchesId)
            ->whereBetween('date',[$date->startOfMonth()->format('Y-m-d'),$date->endOfMonth()->format('Y-m-d')])
            ->count();
        }
        return $data;
    }

    protected static ?array $options = [
        'scales' => [
                'yAxes'=>[
                    'title' => [
                            'display'=> true,
                            'text'=> 'Sales',
                        ],
                    'ticks' => [
                        'precision'=> 0
                        ],
                    ],
            ]
        ];
}
