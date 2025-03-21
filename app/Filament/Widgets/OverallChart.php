<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use Filament\Widgets\ChartWidget;
use Livewire\Attributes\On;
use Str;

class OverallChart extends ChartWidget
{
    public $users;
    public $from;
    public $to;
    public $user_id;
    protected static ?string $maxHeight = '300px';
    public string $dataChecksum = '';


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

    #[On('updateVisitsList')]

    public function updateVisitsList($eventData)
    {
        $this->from = $eventData['from'];
        $this->to = $eventData['to'];
        $this->user_id = $eventData['user_id'];
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



    public function getHeading(): string
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
    public function updateChartData(): void
    {
        $newData = $this->getData();
        $newDataChecksum = md5(json_encode($newData));

        if ($this->dataChecksum !== $newDataChecksum) {
            $this->dataChecksum = $newDataChecksum;
            $this->dispatch('updateChartData', data: $newData);
        }
    }
}
