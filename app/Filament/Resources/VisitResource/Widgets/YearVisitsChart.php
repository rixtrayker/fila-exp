<?php

namespace App\Filament\Resources\VisitResource\Widgets;

use App\Models\Visit;
use Filament\Widgets\BarChartWidget;

class YearVisitsChart extends BarChartWidget
{
    protected static ?string $maxHeight = '300px';

    protected function getHeading(): string
    {
        return 'Monthly Visits';
    }
    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => '',
                    'data' => $this->getChartData(0),
                ],
                [
                    'label' => '',
                    'data' => $this->getChartData(1),
                ],
            ],
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
    private function getChartData($success){

        $data = [];
        foreach ($this->getYearPeriod() as $date)
        {
            Visit::query()
                ->where('status',['verified','visited'][$success])
                ->whereBetween('visit_date',[$date->startOfMonth()->format('Y-m-d'),$date->endOfMonth()->format('Y-m-d')])
                ->get()
                ->toArray();
        }
        return $data;
    }
    private function getYearPeriod(){
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $period = $startOfMonth->subMonths(12)->monthsUntil($endOfMonth);

        return $period;
    }
}
