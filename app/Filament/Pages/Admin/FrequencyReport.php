<?php

namespace App\Filament\Pages\Admin;

use App\Filament\Widgets\FrequencyFilterFormWidget;
use App\Models\Visit;
use App\Exports\ExportVisits;
use App\Models\Client;
use Filament\Pages\Actions\Action;
use Filament\Pages\Page;
use Filament\Forms\Concerns\HasFormComponentActions;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class FrequencyReport extends Page
{
    use HasFormComponentActions;
    use InteractsWithFormActions;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.admin.pages.frequency-report';

    protected static ?string $navigationLabel = 'Frequency report';
    // protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $slug = 'frequency-report';
    protected static ?string $navigationGroup = 'Reports';

    // protected static ?int $navigationSort = 11;
    public $clients;
    public $pending;
    public $missed;
    public $from;
    public $to;
    public $user_id;
    public $grade;
    public $query = [];

    // public function queryString(){
    //     return [
    //         'from',
    //         'to',
    //         'user_id',
    //         'grade',
    //     ];
    // }
    public function __construct()
    {
        if(
            !$this->from &&
            !$this->to &&
            !$this->user_id &&
            !$this->grade
        )
        {
            $this->from = $this->from ?? today()->subDays(7);
            $this->to = $this->to ?? today()->format('Y-m-d');
        }
        $this->initData();
    }


    #[On('updateReportData')]
    public function updateReportData($eventData)
    {
        $this->from = $eventData['from'];
        $this->to = $eventData['to'];
        $this->user_id = $eventData['user_id'];
        $this->grade = $eventData['grade'];
        $this->initData();
    }
    public function getReportQuery(){
        $query =  Client::query()
            ->select('clients.id as id',
                'name_en')
            ->selectRaw('SUM(CASE WHEN visits.status = "visited" THEN 1 ELSE 0 END) AS done_visits_count')
            ->selectRaw('SUM(CASE WHEN visits.status IN ("pending", "planned") THEN 1 ELSE 0 END) AS pending_visits_count')
            ->selectRaw('SUM(CASE WHEN visits.status = "cancelled" THEN 1 ELSE 0 END) AS missed_visits_count')
            ->selectRaw('COUNT(*) AS total_visits_count')
            ->rightJoin('visits', 'clients.id', '=', 'visits.client_id')
            ->groupBy('clients.id','clients.name_en');


        $query->when($this->user_id,
            fn (Builder $query, $ids): Builder => $query
                ->whereIn('visits.user_id', $ids)
        );

        $query->when($this->grade,
            fn (Builder $query, $grade): Builder => $query->where('grade', $grade)
        );

        $query->when($this->from,
            fn (Builder $query, $date): Builder => $query->whereDate('visits.visit_date', '>=', $date)
        );

        $query->when($this->to,
            fn (Builder $query, $date): Builder => $query->whereDate('visits.visit_date', '<=', $date)
        );

        return $query;
    }

    public function updatedFromDate(){
        $this->initData();
    }
    public function updatedToDate(){
        $this->initData();
    }

    protected function getHeaderWidgets(): array
    {
        return [
            FrequencyFilterFormWidget::class,
        ];
    }

    public function initData()
    {
        $this->clients = $this->getReportQuery()->get();
    }

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         Action::make('Export')
    //             ->icon('heroicon-o-document-chart-bar')
    //             ->color('warning')
    //             ->action(function(){
    //                 return (new ExportVisits($this->visited, $this->pending, $this->missed))->download('visits-'.now().'.xlsx');
    //             }),
    //     ];
    // }

}
