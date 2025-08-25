<?php

namespace App\Filament\Pages\Admin;

use App\Filament\Widgets\VisitsFilterFormWidget;
use App\Models\Visit;
use App\Exports\ExportVisits;
use App\Models\Client;
use Filament\Pages\Actions\Action;
use Filament\Pages\Page;
use Filament\Forms\Concerns\HasFormComponentActions;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use App\Traits\ResourceHasPermission;
class VisitReport extends Page
{
    use HasFormComponentActions;
    use InteractsWithFormActions;
    use ResourceHasPermission;

    protected static ?string $permissionName = 'visit-report';

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static string $view = 'filament.admin.pages.visits-report';

    protected static ?string $navigationLabel = 'Visits report';
    protected static ?string $slug = 'visits-report-test';
    protected static ?string $navigationGroup = 'Reports';

    // mmmm disable this page from being accessed

    // canAccess
    public static function canAccess(): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return false;
    }
    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public $visits;
    public $from;
    public $to;
    public $client_type_id;
    public $user_id;
    public $grade;

    public function __construct()
    {
        $this->from = $this->from ?? today()->subDays(7);
        $this->to = $this->to ?? today()->format('Y-m-d');
        $this->initData();
    }


    #[On('updateVisitReportData')]
    public function updateVisitReportData($eventData)
    {
        $this->from = $eventData['from'];
        $this->to = $eventData['to'];
        $this->user_id = $eventData['user_id'];
        $this->grade = $eventData['grade'];
        $this->client_type_id = $eventData['client_type_id'];
        $this->initData();
    }
    public function getReportQuery(){
        $query =  Visit::query()
            ->select(
                DB::raw('GROUP_CONCAT(DISTINCT users.name SEPARATOR ", ") as medical_rep'),
                DB::raw('GROUP_CONCAT(DISTINCT visit_date SEPARATOR ", ") as visit_date'),
                DB::raw('GROUP_CONCAT(DISTINCT comment SEPARATOR ", ") as comment'),
                DB::raw('GROUP_CONCAT(DISTINCT visits.status SEPARATOR ", ") as status'),
                DB::raw('GROUP_CONCAT(DISTINCT clients.name_en SEPARATOR ", ") as client_name')
                ,DB::raw('GROUP_CONCAT(products.name SEPARATOR", ") AS products_list')
            )
            ->leftJoin('users', 'visits.user_id', '=', 'users.id')
            ->leftJoin('clients', 'visits.client_id', '=', 'clients.id')
            ->leftJoin('product_visits', 'product_visits.visit_id', '=', 'visits.id')
            ->leftJoin('products', 'product_visits.product_id', '=', 'products.id')
            ->groupBy('visits.id','clients.id')
            ->orderBy('visit_date', 'DESC');



        $query->when($this->user_id,
            fn (Builder $query, $ids): Builder => $query
                ->whereIn('user_id', $ids)
        );

        $query->when($this->grade,
            fn (Builder $query, $grade): Builder => $query->where('grade', $grade)
        );


        $query->when($this->client_type_id,
            fn (Builder $query, $ids): Builder => $query->whereIn('client_type_id', $ids)
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
            VisitsFilterFormWidget::class,
        ];
    }

    public function initData()
    {
        $this->visits = $this->getReportQuery()->get();
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
