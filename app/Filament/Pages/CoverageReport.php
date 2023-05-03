<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FilterFormWidget;
use App\Filament\Widgets\OverallChart;
use App\Models\Visit;
use Filament\Pages\Page;

class CoverageReport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.coverage-report';

    protected static ?string $navigationLabel = 'Cover report';
    // protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $slug = 'cover-report';

    protected static ?int $navigationSort = 11;
    public $visited;
    public $pending;
    public $missed;
    public $from;
    public $to;
    public $ids = [];
    public $query = [];
    public function queryString(){

        return [
            'from' => ['except' => ''],
            'to' => ['except' => ''],
            'ids' => ['except' => ''],
        ];
    }

    public function __construct()
    {
        $this->ids = [auth()->id()];
        $this->from = '2023-01-01';
        $this->to = '2023-12-31';

        $this->visited = $this->getVisits()->visited()->get();
        $this->pending = $this->getVisits()->pending()->get();
        $this->missed = $this->getVisits()->missed()->get();
    }

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

        return $query;
    }
    // public static function getPages(): array
    // {
    //     return [
    //         // ...
    //         'cover-report' => CoverageReport::route('/cover-report'),
    //     ];
    // }
    protected function getHeaderWidgets(): array
    {
        return [
            OverallChart::class,
            FilterFormWidget::class,
        ];
    }

    // protected function getViewData(): array
    // {
    //     return [
    //         OverallChart::class,
    //     ];
    // }
}
