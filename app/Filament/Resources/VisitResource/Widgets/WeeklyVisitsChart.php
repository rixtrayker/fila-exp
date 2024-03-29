<?php

namespace App\Filament\Resources\VisitResource\Widgets;

use App\Models\Plan;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WeeklyVisitsChart extends ChartWidget
{
    protected static ?string $maxHeight = '300px';

    private static $plans;
    private static $labels;
    private static $planDates = [];

    protected function getType(): string
    {
        return 'bar';
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
        return 'Weekly Visits';
    }


    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    private function getLabels(): array {
        $data = [];

        foreach (self::getPlansDates() as $date)
        {
            $carbon = new Carbon($date);
            $data[] = $carbon->day.' '.$carbon->monthName;
        }

        return $data;
    }

    private static function getLatestPlans(): Collection{
        if(self::$plans)
            return self::$plans;

        self::$plans = Plan::latest()
            ->whereIn('start_at', self::getPlansDates())
            ->select(['start_at', 'id'])
            ->with('visits')
            ->get();

        return self::$plans;
    }

    private static function getPlansDates(): array
    {
        if(self::$planDates)
            return self::$planDates;

        self::$planDates = Plan::selectRaw('DISTINCT start_at')
            ->orderBy('start_at', 'desc')
            ->limit(12)
            ->get()
            ->pluck('start_at')
            ->map(fn($item)=>$item->format('Y-m-d'))
            ->toArray();

        return self::$planDates;
    }

    private function getDataSets()
    {
        $datasets = [];
        $visitsTypes = [
            'visited' => 'Done',
            'pending' => 'Pending',
            'cancelled' => 'Missed'
        ];
        //$visitsTypes = ['Done', 'Pending', 'Missed'];
    //self::$plans as $key => $plan
        $i = 0;
        foreach($visitsTypes as $key => $visit) {
            $datasets[] = [
                'label' => $visit,
                'data' => $this->getChartData($key),
                'backgroundColor' => $this->backgroundColor[$i],
                'borderColor' => $this->borderColor[$i],
                'borderWidth' => 1
            ];
            $i++;
        }
        return $datasets;
    }

    // 'label' => self::getLabels()[$key],
    // 'data' => $this->getChartData(self::getLabels()[$key]),

    private function getChartData($status){

        $data = [];
        $plans = self::getLatestPlans();

        foreach ( self::getPlansDates() as $date)
        {
            $count = 0;

            foreach($plans->where('start_at', new Carbon($date)) as $plan){
                $count += self::getPlansData($plan, $status);

                if($status == 'pending'){
                    $count += self::getPlansData($plan, 'planned');
                }
            }

            $data[] = $count;
        }
        return $data;
    }

    private static function getPlansData($plan, $status): int
    {
        return $plan->visits?->where('status', $status)->count() ?? 0;
    }

    protected function getData(): array
    {
        return [
            'datasets' => $this->getDataSets(),
            'labels' => $this->getLabels(),
        ];
    }
}
