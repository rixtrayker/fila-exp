<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FilterFormWidget;
use App\Filament\Widgets\OverallChart;
use App\Models\Visit;
use App\Exports\ExportVisits;
use Filament\Pages\Actions\Action;
use Filament\Pages\Page;
use Filament\Forms\Concerns\HasFormComponentActions;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Livewire\Attributes\On;
use Maatwebsite\Excel\Excel;

class CoverageReport extends Page
{
    use HasFormComponentActions;
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.coverage-report';

    protected static ?string $navigationLabel = 'Coverage report';
    // protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $slug = 'cover-report';
    protected static ?string $navigationGroup = 'Reports';

    // protected static ?int $navigationSort = 11;
    public $visited;
    public $pending;
    public $missed;
    public $from;
    public $to;
    public $user_id = [];
    public $query = [];

    #[On('updateVisitsList')]

    public function updateVisitsList($eventData)
    {
        $this->from = $eventData['from'];
        $this->to = $eventData['to'];
        $this->user_id = $eventData['user_id'];
        $this->initData();
    }

    public function __construct()
    {
        $this->initData();
    }

    public function getVisits(){
        $query =  Visit::query();

        if($this->user_id){

            $query->where(function($q) {
                $q->whereIn('user_id', $this->user_id);
                $q->orWhereIn('second_user_id', $this->user_id);
            });
        }

        if($this->from){
            $query->whereDate('visit_date','>=', $this->from);
        }

        if($this->to){
            $query->whereDate('visit_date','<=',$this->to);
        }

        return $query;
    }

    public function updatedFrom(){
        $this->initData();
    }
    public function updatedUserId(){
        $this->initData();
    }
    public function updatedTo(){
        $this->initData();
    }
    public function updatedMissed(){
    }
    public function updatedPending(){
    }

    protected function getHeaderWidgets(): array
    {
        return [
            OverallChart::class,
            FilterFormWidget::class,
        ];
    }

    public function initData()
    {
        $this->visited = $this->getVisits()->with('client')->visited()->get();
        $this->pending = $this->getVisits()->pending('client')->get();
        $this->missed = $this->getVisits()->missed('client')->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Export')
                ->icon('heroicon-o-document-chart-bar')
                ->color('warning')
                ->action(function(){
                    return (new ExportVisits($this->visited, $this->pending, $this->missed))->download('visits-'.now().'.xlsx');
                }),
        ];
    }

}
