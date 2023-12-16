<?php

namespace App\Filament\Resources\VacationsReportResource\Pages;

use App\Filament\Resources\VacationsReportResource;
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

class ListVacationsReports extends ListRecords implements HasInfolists
{
    use InteractsWithInfolists;

    protected static string $resource = VacationsReportResource::class;
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
                TextEntry::make('total_spent_days')
                    ->label('Spent Days'),
                TextEntry::make('total_remaining_days')
                    ->hint('These reps only')
                    ->label('Remaining Days'),
                TextEntry::make('vacation_types')
                    ->label('Vacation Types'),
                TextEntry::make('approved_count')
                    ->label('Approved Vacations')
                    ->badge()
                    ->color('success')
                    ->size(TextEntry\TextEntrySize::Large)
                    ->icon('heroicon-m-check-circle')
                    ->iconPosition(IconPosition::After),
                TextEntry::make('pending_count')
                    ->label('Pending Vacations')
                    ->badge()
                    ->color('warning')
                    ->size(TextEntry\TextEntrySize::Large)
                    ->icon('heroicon-m-clock')
                    ->iconPosition(IconPosition::After),
                TextEntry::make('rejected_count')
                    ->label('Rejected Vacations')
                    ->badge()
                    ->color('danger')
                    ->size(TextEntry\TextEntrySize::Large)
                    ->icon('heroicon-m-x-circle')
                    ->iconPosition(IconPosition::After),
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
        $summary['approved_count'] = $records->where('approved','>', 0)->count();
        $summary['pending_count'] = $records->where('approved', 0)->count();
        $summary['approved_count'] = $records->where('approved','>', 0)->count();
        $summary['rejected_count'] = $records->where('approved','<', 0)->where('approved','!=')->pluck('approved')->filter()->count();
        $summary['medical_reps_count'] = count(array_unique($records->pluck('user_id')->toArray()));
        $vacation_types = $records->pluck('vacation_types')->map(fn($item) => explode(', ',$item))->flatten()->map(fn($item) => trim($item))->unique();
        $summary['vacation_types'] = $vacation_types->implode(', ');
        $summary['total_spent_days'] = $records->sum('spent_days');
        $summary['total_remaining_days'] = 21 *  $summary['medical_reps_count'] - $summary['total_spent_days'];
        $model = new ReportSummary();
        $model->fill($summary);
        return $model;
    }
}
