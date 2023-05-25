<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\PieChartWidget;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Log;
use Str;

class OverallChart extends PieChartWidget
{
    public $users;
    public $from;
    public $to;
    public $user_id;
    protected static ?string $maxHeight = '300px';


    protected $listeners = [
        'updateVisitsList' => 'updateVisitsList',
    ];
    public function updateVisitsList($from, $to, $user_id)
    {
        $this->from = $from;
        $this->to = $to;
        $this->user_id = $user_id;
        $this->updateChartData();
    }

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
        $data = [
            $this->getVisits()->visited()->count(),
            $this->getVisits()->missed()->count(),
            $this->getVisits()->pending()->count()
        ];

        $datasets = [
        [
            'label' => 'Visit type',
            'data'=> $data,
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

        if($this->user_id){
            $query->whereIn('user_id',$this->user_id)->orWhereIn('second_user_id',$this->user_id);
        }

        if($this->from){
            $query->whereDate('visit_date','>=',$this->from);
        }

        if($this->to){
            $query->whereDate('visit_date','<',$this->to);
        }
        return $query;
    }



    public function updateChartData()
    {
        $newDataChecksum = $this->generateDataChecksum();

        if ($newDataChecksum !== $this->dataChecksum) {
            $this->dataChecksum = $newDataChecksum;

            $this->emitSelf('updateChartData', [
                'data' => $this->getData(),
            ]);
        }
    }

    public static function canView(): bool
    {
        return Str::contains(request()->path(),'cover-report');
    }
}
