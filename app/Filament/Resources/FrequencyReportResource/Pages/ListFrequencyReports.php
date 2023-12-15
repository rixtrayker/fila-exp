<?php

namespace App\Filament\Resources\FrequencyReportResource\Pages;

use App\Filament\Resources\FrequencyReportResource;
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

    public function getTableRecordKey(Model $model):string
    {
        return 'id';
    }

    public function summaryInfolist(Infolist $infolist): Infolist
    {
        return $infolist
                ->record($this->getSummary())
                ->schema([
                    TextEntry::make('')
                        ->label('Done, Pending, Missed, Total Visits')
                        ->columnSpanFull()
                        ->size(TextEntry\TextEntrySize::Large)
                        ->weight(FontWeight::ExtraBold),
                    TextEntry::make('from_date')
                        ->label('From Date'),
                    TextEntry::make('to_date')
                        ->columnSpan(3)
                        ->label('To Date'),
                    TextEntry::make('doctors_count')
                        ->label('Visited Doctors'),
                    TextEntry::make('distinct_rep_visits_count')
                        ->label('Medical Reps'),
                    TextEntry::make('grade')
                        ->label('Grades'),
                    TextEntry::make('bricks_count')
                        ->label('Bricks Count'),
                    TextEntry::make('done_visits_count')
                        ->badge()
                        ->color('success')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->icon('heroicon-m-check-circle')
                        ->iconPosition(IconPosition::After),
                    TextEntry::make('pending_visits_count')
                        ->badge()
                        ->color('warning')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->icon('heroicon-s-clock')
                        ->iconPosition(IconPosition::After),
                    TextEntry::make('missed_visits_count')
                        ->badge()
                        ->color('danger')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->icon('heroicon-m-x-circle')
                        ->iconPosition(IconPosition::After),
                    TextEntry::make('total_visits_count')
                        ->badge()
                        ->color('gray')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->icon('heroicon-m-list-bullet')
                        ->iconPosition(IconPosition::After),
                ])
            ->columns([
                'sm' => 2,
                'xl' => 4,
                '2xl' => 4,
            ]);
    }

    private function getSummary(): Model{
        $records = $this->table->getRecords();
        $summary['doctors_count'] = $records->count();
        $summary['grade'] = implode(', ', array_unique($records->pluck('grade')->toArray()));
        $summary['bricks_count'] = count(array_unique($records->pluck('brick_id')->toArray()));
        $summary['from_date'] = $this->table->getFilter('visit_date')->getState()['from_date'];
        $summary['to_date'] = $this->table->getFilter('visit_date')->getState()['to_date'];
        $summary['done_visits_count'] = $records->sum('done_visits_count');
        $summary['missed_visits_count'] = $records->sum('missed_visits_count');
        $summary['pending_visits_count'] = $records->sum('pending_visits_count');
        $summary['total_visits_count'] = $records->sum('total_visits_count');
        $summary['distinct_rep_visits_count'] = Visit::whereIn('client_id', $records->pluck('id'))
            ->where('visit_date', '>=', $summary['from_date'])
            ->where('visit_date', '<=', $summary['to_date'])
            ->distinct('user_id')
            ->count('user_id');
        $model = new ReportSummary();
        $model->fill($summary);
        return $model;
    }
}
