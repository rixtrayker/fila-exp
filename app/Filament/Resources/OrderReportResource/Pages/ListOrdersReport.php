<?php

namespace App\Filament\Resources\OrderReportResource\Pages;

use App\Filament\Resources\OrderReportResource;
use App\Filament\Widgets\Charts\OrderAreaChart;
use App\Models\Reports\ReportSummary;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;


class ListOrdersReport extends ListRecords  implements HasInfolists
{
    use InteractsWithInfolists;
    protected static string $resource = OrderReportResource::class;
    use ExposesTableToWidgets;

    protected function getHeaderWidgets(): array
    {
        return [
            // BusinessOrderCompanyQuantity::class,
            OrderAreaChart::class
        ];
    }

    protected static string $view = 'filament.admin.pages.list-report-with-summary';

    public function summaryInfolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->getSummary())
            ->schema([
                TextEntry::make('from_date')
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 1,
                        'xl' => 2,
                    ])
                    ->label('From Date'),
                TextEntry::make('to_date')
                    ->columnSpan([
                        'sm' => 1,
                        'md' => 1,
                        'xl' => 2,
                    ])
                    ->label('To Date'),
                TextEntry::make('clients_count')
                    ->label('Clients count'),
                TextEntry::make('medical_reps_count')
                    ->label('Medical Reps'),
                TextEntry::make('products_list')
                    ->label('Products List'),
                TextEntry::make('areas_count')
                    ->label('Areas Count')
                    ->columnSpan(['lg' => 1, 'sm' => 1]),
            ])
            ->columns([
                'sm' => 1,
                'md' => 2,
                'lg' => 2,
                'xl' => 4,
            ]);
    }

    private function getSummary(): Model{
        $query = self::$resource::getEloquentQuery();
        $records = $this->applyFiltersToTableQuery($query)->get();
        $summary['medical_reps_count'] =  count(array_unique($records->pluck('user_id')->toArray()));
        $summary['clients_count'] =  count(array_unique($records->pluck('client_id')->toArray()));
        // $summary['companies_count'] = count(array_unique($records->pluck('company_id')->toArray()));
        // $summary['branches_count'] = count($companyBranchesIds);
        $areas =  $records->pluck('area_id')->toArray();
        $summary['areas_count'] = count(array_unique($areas));
        $products = [];
        foreach($records->pluck('product_list_report') as $pl){
            $products = $products + explode(',' , $pl);
        }
        $summary['products_list'] = implode(', ', $products);
        $summary['from_date'] = $this->table->getFilter('dates')->getState()['from_date'];
        $summary['to_date'] = $this->table->getFilter('dates')->getState()['to_date'];

        $model = new ReportSummary();
        $model->fill($summary);
        return $model;
    }

}
