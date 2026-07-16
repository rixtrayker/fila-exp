<?php

namespace App\Filament\Resources\VacationsReportResource\Pages;

use App\Filament\Resources\VacationsReportResource;
use App\Models\Reports\ReportSummary;
use App\Models\User;
use App\Services\VacationEntitlementService;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
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

    public function getTableRecordKey(Model $model): string
    {
        return (string) $model->getAttribute('id');
    }

    public function getBreadcrumbs(): array
    {
        return [];
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

    private function getSummary(): Model
    {
        $query = self::$resource::getEloquentQuery();
        $records = $this->applyFiltersToTableQuery($query)->get();
        [$fromDate, $toDate] = VacationsReportResource::getSelectedDateRange($this);
        $users = User::withoutGlobalScopes()
            ->whereIn('id', $records->pluck('user_id'))
            ->get()
            ->keyBy('id');
        $entitlements = app(VacationEntitlementService::class);

        $summary['from_date'] = $fromDate->toDateString();
        $summary['to_date'] = $toDate->toDateString();
        $summary['approved_count'] = $records->sum('approved_count');
        $summary['pending_count'] = $records->sum('pending_count');
        $summary['rejected_count'] = $records->sum('rejected_count');
        $summary['medical_reps_count'] = $users->count();
        $vacationTypes = $records
            ->pluck('vacation_types')
            ->map(fn ($item) => explode(', ', $item))
            ->flatten()
            ->map(fn ($item) => trim($item))
            ->filter()
            ->unique();
        $summary['vacation_types'] = $vacationTypes->implode(', ');
        $summary['total_spent_days'] = $users->sum(
            fn (User $user) => $entitlements->spentDaysInRange($user, $fromDate, $toDate),
        );
        $summary['total_remaining_days'] = $users->sum(
            fn (User $user) => $entitlements->remainingAnnualDays($user, now()->year),
        );
        $model = new ReportSummary();
        $model->fill($summary);

        return $model;
    }
}
