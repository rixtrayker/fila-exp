<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\PieChartWidget;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Log;

class OverallChart extends PieChartWidget
{
    public $users;
    public $from;
    public $to;
    public $ids;
    public $ddd=10;
    protected static ?string $maxHeight = '300px';

    protected static string $view = 'filament.widgets.chart-widget';

    protected $listeners = [
        'updateUsersList',
        'updateFromToDates,'
    ];

    protected $backgroundColor = [
        'rgba(75, 192, 192, 0.2)',
        'rgba(255, 99, 132, 0.2)',
        'rgba(54, 162, 235, 0.2)',
    ];
    protected $borderColor = [
        'rgb(75, 192, 192)',
        'rgb(255, 99, 132)',
        'rgb(54, 162, 235)',
    ];



    protected function getHeading(): string
    {
        return 'Visits Overall Chart';
    }

    public function getColumnSpan(): int|string|array
    {
        return 1;
    }
    public function updatedFrom(){
        $this->updateChartData();
    }
    public function updatedIds(){
        $this->updateChartData();
    }
    public function updatedTo(){
        $this->updateChartData();
    }

    protected function getData(): array
    {
        return [
            'datasets' => $this->getDataSets(),
            'labels' => $this->getLabels(),
        ];
    }
    private function getLabels(){
        $labels = ['Done Visits','Missed Visits','Pending visits'];
        return $labels;
    }

    private function getDataSets()
    {
        $datasets = [
        [
            'label' => 'Visit type',
            'data'=> [
                $this->ddd,
               30,30

            ],
            'backgroundColor' => $this->backgroundColor,
            'borderColor' => $this->borderColor,
        ],

        ];
        return $datasets;
    }

    // public function updateUsersList(){
    //     //  $this->users = $ids;
    //      Log::channel('debugging')->info(1);
    // }
    public function getVisits(){
        $query =  Visit::query();

        if($this->ids){

            $query->whereIn('user_id',$this->ids)->orWhereIn('second_user_id',$this->ids);
        }

        if($this->from){
            $query->whereDate('visit_date','>=',$this->from);
        }

        if($this->to){
            $query->whereDate('visit_date','<',$this->to);
        }
    }


    protected static ?array $options = [
        'offset'=>2,
    ];

    public function updateChartData()
    {
        $newDataChecksum = $this->generateDataChecksum();

        if ($newDataChecksum !== $this->dataChecksum) {
            $this->dataChecksum = $newDataChecksum;

            $this->emitSelf('updateChartData', [
                'data' => $this->getCachedData(),
            ]);
        }
    }
}
