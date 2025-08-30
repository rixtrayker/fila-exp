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
                TextEntry::make('total_expenses')
                    ->label('Total Expenses')
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
        $summary['total_expenses'] = $records->sum('total_expenses');
        $model = new ReportSummary();
        $model->fill($summary);
        return $model;
    }
}
