<?php

namespace App\Filament\Resources\SalesReportResource\Pages;

use App\Filament\Resources\SalesReportResource;
use App\Filament\Widgets\Charts\BusinessOrderCompanyQuantity;
use App\Filament\Widgets\Charts\BusinessOrderProductQuantity;
use App\Models\Reports\ReportSummary;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Actions;
use Filament\Pages\Concerns\ExposesTableToWidgets;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;


class ListSalesReport extends ListRecords  implements HasInfolists
{
    use InteractsWithInfolists;
    protected static string $resource = SalesReportResource::class;
    use ExposesTableToWidgets;

    protected function getHeaderWidgets(): array
    {
        return [
            BusinessOrderCompanyQuantity::class,
            BusinessOrderProductQuantity::class,
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
                TextEntry::make('companies_count')
                    ->label('Companies Count'),
                TextEntry::make('branches_count')
                    ->label('Branches Count'),
                TextEntry::make('products_list')
                    ->label('Products List'),
                // TextEntry::make('bricks_count')
                //     ->label('Bricks Count')
                //     ->columnSpan(['lg' => 1, 'sm' => 1]),
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
        $companyBranchesIds = array_unique($records->pluck('company_branch_id')->toArray());

        $summary['medical_reps_count'] =  count(array_unique($records->pluck('user_id')->toArray()));
        $summary['companies_count'] = count(array_unique($records->pluck('company_id')->toArray()));
        $summary['branches_count'] = count($companyBranchesIds);
        // $bricksIds = CompanyBranch::whereIn('id', $companyBranchesIds)->pluck('brick_id')->toArray();
        // $summary['bricks_count'] = count(array_unique($bricksIds));
        $summary['products_list'] = implode(', ', array_unique($records->pluck('product_name')->toArray()));
        $summary['from_date'] = $this->table->getFilter('dates')->getState()['from_date'];
        $summary['to_date'] = $this->table->getFilter('dates')->getState()['to_date'];

        $model = new ReportSummary();
        $model->fill($summary);
        //write raw sql query to get distinct client_id form table visits where user_id
        // ex. select distinct client_id from visits where user_id = 1;
        return $model;
    }

}
