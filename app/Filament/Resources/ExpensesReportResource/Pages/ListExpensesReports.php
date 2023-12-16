<?php

namespace App\Filament\Resources\ExpensesReportResource\Pages;

use App\Filament\Resources\ExpensesReportResource;
use App\Models\Reports\ReportSummary;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Model;

class ListExpensesReports extends ListRecords implements HasInfolists
{
    use InteractsWithInfolists;

    protected static string $resource = ExpensesReportResource::class;
    protected static string $view = 'filament.admin.pages.list-report-with-summary';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function getTableRecordKey(Model $model):string
    {
        return 'id';
    }

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
                TextEntry::make('medical_reps_count')
                    ->label('Medical Reps'),
                TextEntry::make('total_expenses')
                    ->label('Total Expenses'),
                TextEntry::make('transportation')
                    ->label('Transportation'),
                TextEntry::make('lodging')
                    ->label('Lodging'),
                TextEntry::make('mileage')
                    ->label('Mileage'),
                TextEntry::make('meal')
                    ->label('Meal'),
                TextEntry::make('telephone_postage')
                    ->label('Telephone Postage'),
                TextEntry::make('medical_expenses')
                    ->label('Medical Expenses'),
                TextEntry::make('others')
                    ->label('Others'),
                TextEntry::make('total')
                    ->label('Total')
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
        $summary['from_date'] = $this->table->getFilter('dates_range')->getState()['from_date'];
        $summary['to_date'] = $this->table->getFilter('dates_range')->getState()['to_date'];
        $summary['medical_reps_count'] = $records->count();
        $summary['transportation'] = $records->sum('transportation');
        $summary['lodging'] = $records->sum('lodging');
        $summary['mileage'] = $records->sum('mileage');
        $summary['meal'] = $records->sum('meal');
        $summary['telephone_postage'] = $records->sum('telephone_postage');
        $summary['medical_expenses'] = $records->sum('medical_expenses');
        $summary['others'] = $records->sum('others');
        $summary['total'] = $records->sum('total');
        $summary['total_expenses'] = $records->sum('total');
        $model = new ReportSummary();
        $model->fill($summary);
        return $model;
    }
}
