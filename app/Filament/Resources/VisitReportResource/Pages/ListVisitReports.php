<?php

namespace App\Filament\Resources\VisitReportResource\Pages;

use App\Filament\Resources\VisitReportResource;
use App\Models\Reports\ReportSummary;
use App\Models\Visit;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Model;

class ListVisitReports extends ListRecords implements HasInfolists
{
    use InteractsWithInfolists;

    protected static string $resource = VisitReportResource::class;
    protected static string $view = 'filament.admin.pages.list-report-with-summary';

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
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
                    ->label('From Date'),
                TextEntry::make('to_date')
                    ->columnSpan(3)
                    ->label('To Date'),
                TextEntry::make('doctors_count')
                    ->label('Visited Doctors'),
                TextEntry::make('medical_reps_count')
                    ->label('Medical Reps'),
                TextEntry::make('grade')
                    ->label('Grades'),
                TextEntry::make('visit_types')
                    ->label('Visit Types'),
                TextEntry::make('bricks_names')
                    ->columnSpan(['lg' => 3, 'sm' => 1])
                    ->label('Bricks Names'),
                TextEntry::make('bricks_count')
                    ->label('Bricks Count')
                    ->columnSpan(['lg' => 1, 'sm' => 1]),
                TextEntry::make('done_visits_count')
                    ->label('Done Visits')
                    ->badge()
                    ->color('success')
                    ->size(TextEntry\TextEntrySize::Large)
                    ->icon('heroicon-m-check-circle')
                    ->iconPosition(IconPosition::After),
                TextEntry::make('pending_visits_count')
                    ->label('Planned & Pending Visits')
                    ->badge()
                    ->color('warning')
                    ->size(TextEntry\TextEntrySize::Large)
                    ->icon('heroicon-s-clock')
                    ->iconPosition(IconPosition::After),
                TextEntry::make('missed_visits_count')
                    ->label('Missed Visits')
                    ->badge()
                    ->color('danger')
                    ->size(TextEntry\TextEntrySize::Large)
                    ->icon('heroicon-m-x-circle')
                    ->iconPosition(IconPosition::After),
                TextEntry::make('total_visits_count')
                    ->label('Total Visits')
                    ->badge()
                    ->color('gray')
                    ->size(TextEntry\TextEntrySize::Large)
                    ->icon('heroicon-m-list-bullet')
                    ->iconPosition(IconPosition::After),
            ])
        ->columns([
            'sm' => 1,
            'md' => 2,
            'xl' => 4,
        ]);
    }

    private function getSummary(): Model{
        $query = self::$resource::getEloquentQuery();
        $records = $this->applyFiltersToTableQuery($query)->get();
        $summary['medical_reps_count'] =  count(array_unique($records->pluck('user_id')->toArray()));
        $summary['doctors_count'] = count(array_unique($records->pluck('client_id')->toArray()));
        $summary['grade'] = implode(', ', array_unique($records->pluck('grade')->toArray()));
        $summary['bricks_count'] = count(array_unique($records->pluck('brick_id')->toArray()));
        $summary['bricks_names'] = implode(', ', array_unique($records->pluck('brick_name')->toArray()));
        $summary['visit_types'] = implode(', ', array_unique($records->pluck('visit_types')->toArray()));
        $summary['from_date'] = $this->table->getFilter('visit_date')->getState()['from_date'];
        $summary['to_date'] = $this->table->getFilter('visit_date')->getState()['to_date'];
        $summary['done_visits_count'] = $records->sum('done_visits_count');
        $summary['missed_visits_count'] = $records->sum('missed_visits_count');
        $summary['pending_visits_count'] = $records->sum('pending_visits_count');
        $summary['total_visits_count'] = $records->sum('total_visits_count');
        $summary['distinct_rep_visits_count'] = count(array_unique($records->pluck('user_id')->toArray()));
        $model = new ReportSummary();
        $model->fill($summary);
        //write raw sql query to get distinct client_id form table visits where user_id
        // ex. select distinct client_id from visits where user_id = 1;
        return $model;
    }
}
