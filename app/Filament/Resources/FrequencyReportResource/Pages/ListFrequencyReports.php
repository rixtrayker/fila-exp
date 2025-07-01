<?php

namespace App\Filament\Resources\FrequencyReportResource\Pages;

use App\Filament\Resources\FrequencyReportResource;
use App\Models\Reports\ReportSummary;
use App\Models\Visit;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\View;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
class ListFrequencyReports extends ListRecords implements HasInfolists
{
    use InteractsWithInfolists;
    protected static string $resource = FrequencyReportResource::class;
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
        return 'client_id';
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
                    TextEntry::make('doctors_count')
                        ->label('Visited Doctors'),
                    TextEntry::make('medical_reps_count')
                        ->label('Medical Reps'),
                    TextEntry::make('grade')
                        ->label('Grades'),
                    TextEntry::make('bricks_count')
                        ->label('Bricks Count'),
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
                        ->color('info')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->icon('heroicon-m-list-bullet')
                        ->iconPosition(IconPosition::After),
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
        $dateRange = $this->extractDateRange();
        $filters = $this->extractTableFilters();
        
        $records = $this->getFrequencyReportData($dateRange, $filters);
        $summaryData = $this->buildSummaryData($records, $dateRange);
        
        return $this->createSummaryModel($summaryData);
    }

    private function extractDateRange(): array
    {
        $tableFilters = request()->get('tableFilters', []);
        $dateFilter = $tableFilters['date_range'] ?? [];
        
        return [
            'from_date' => $dateFilter['from_date'] ?? today()->subDays(7)->toDateString(),
            'to_date' => $dateFilter['to_date'] ?? today()->toDateString(),
        ];
    }

    private function extractTableFilters(): array
    {
        $tableFilters = request()->get('tableFilters', []);
        
        return [
            'brick_id' => $tableFilters['brick_id'] ?? null,
            'grade' => $tableFilters['grade'] ?? null,
            'client_type_id' => $tableFilters['client_type_id'] ?? null,
        ];
    }

    private function getFrequencyReportData(array $dateRange, array $filters): Collection
    {
        return \App\Models\Reports\FrequencyReportData::getAggregatedData(
            $dateRange['from_date'],
            $dateRange['to_date'],
            $filters
        );
    }

    private function buildSummaryData(Collection $records, array $dateRange): array
    {
        $summary = [
            'from_date' => $dateRange['from_date'],
            'to_date' => $dateRange['to_date'],
            'doctors_count' => $records->count(),
            'grade' => $this->formatGrades($records),
            'bricks_count' => $this->countUniqueBricks($records),
            'done_visits_count' => $records->sum('done_visits_count'),
            'missed_visits_count' => $records->sum('missed_visits_count'),
            'pending_visits_count' => $records->sum('pending_visits_count'),
            'total_visits_count' => $records->sum('total_visits_count'),
        ];

        $summary['medical_reps_count'] = $this->getMedicalRepsCount($records, $dateRange);

        return $summary;
    }

    private function formatGrades(Collection $records): string
    {
        return implode(', ', array_unique($records->pluck('grade')->filter()->toArray()));
    }

    private function countUniqueBricks(Collection $records): int
    {
        return count(array_unique($records->pluck('brick_name')->filter()->toArray()));
    }

    private function getMedicalRepsCount(Collection $records, array $dateRange): int
    {
        return Visit::whereIn('client_id', $records->pluck('client_id'))
            ->whereDate('visit_date', '>=', $dateRange['from_date'])
            ->whereDate('visit_date', '<=', $dateRange['to_date'])
            ->distinct('user_id')
            ->count('user_id');
    }

    private function createSummaryModel(array $summaryData): Model
    {
        $model = new ReportSummary();
        $model->fill($summaryData);
        
        return $model;
    }
}
