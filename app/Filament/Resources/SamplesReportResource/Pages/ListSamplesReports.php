<?php

namespace App\Filament\Resources\SamplesReportResource\Pages;

use App\Filament\Resources\SamplesReportResource;
use App\Models\Reports\ReportSummary;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListSamplesReports extends ListRecords implements HasInfolists
{
    use InteractsWithInfolists;

    protected static string $resource = SamplesReportResource::class;

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

    public function getTableRecordKey(Model $model): string
    {
        return (string) $model->getAttribute('id');
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
                TextEntry::make('products_count')
                    ->label('Products'),
                TextEntry::make('visits_count')
                    ->label('Visits'),
                TextEntry::make('total_samples')
                    ->label('Total Samples'),
            ])
            ->columns([
                'sm' => 1,
                'md' => 2,
                'lg' => 2,
                'xl' => 4,
            ]);
    }

    private function getSummary(): Model
    {
        $query = self::$resource::getEloquentQuery();
        $records = $this->applyFiltersToTableQuery($query)->get();
        $summary['from_date'] = $this->table->getFilter('dates_range')->getState()['from_date'];
        $summary['to_date'] = $this->table->getFilter('dates_range')->getState()['to_date'];
        $summary['medical_reps_count'] = $records->pluck('user_id')->unique()->count();
        $summary['products_count'] = $records->pluck('product_id')->unique()->count();
        $summary['visits_count'] = $records->pluck('visit_id')->unique()->count();
        $summary['total_samples'] = $records->sum('samples_count');
        $model = new ReportSummary();
        $model->fill($summary);

        return $model;
    }
}
