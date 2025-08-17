<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientCoverageReportResource\Pages;
use App\Models\Client;
use App\Traits\ResourceHasPermission;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\Facades\DB;
use App\Models\Scopes\GetMineScope;

class ClientCoverageReportResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = Client::class;
    protected static ?string $label = 'Client Coverage Report';
    protected static ?string $navigationLabel = 'Client Coverage';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $slug = 'client-coverage-report';
    protected static ?string $permissionName = 'client-coverage-report';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('name')
                    ->label('Client Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('grade')
                    ->label('Grade')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'A' => 'success',
                        'B' => 'info',
                        'C' => 'warning',
                        'N' => 'danger',
                        'PH' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('client_type.name')
                    ->label('Client Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brick.name')
                    ->label('Brick')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('area.name')
                    ->label('Area')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('last_visit_date')
                    ->label('Last Visit Date')
                    ->date()
                    ->sortable()
                    ->getStateUsing(function (Model $record) {
                        return $record->visits()
                            ->where('status', 'visited')
                            ->latest('visit_date')
                            ->value('visit_date');
                    }),
                TextColumn::make('total_visits')
                    ->label('Total Visits')
                    ->numeric()
                    ->sortable()
                    ->getStateUsing(function (Model $record) {
                        return $record->visits()->count();
                    }),
                TextColumn::make('visited_count')
                    ->label('Visited')
                    ->numeric()
                    ->color('success')
                    ->sortable()
                    ->getStateUsing(function (Model $record) {
                        return $record->visits()->where('status', 'visited')->count();
                    }),
                TextColumn::make('planned_count')
                    ->label('Planned')
                    ->numeric()
                    ->color('info')
                    ->sortable()
                    ->getStateUsing(function (Model $record) {
                        return $record->visits()->where('status', 'planned')->count();
                    }),
                TextColumn::make('pending_count')
                    ->label('Pending')
                    ->numeric()
                    ->color('warning')
                    ->sortable()
                    ->getStateUsing(function (Model $record) {
                        return $record->visits()->where('status', 'pending')->count();
                    }),
                TextColumn::make('missed_count')
                    ->label('Missed')
                    ->numeric()
                    ->color('danger')
                    ->sortable()
                    ->getStateUsing(function (Model $record) {
                        return $record->visits()->where('status', 'missed')->count();
                    }),
                TextColumn::make('coverage_percentage')
                    ->label('Coverage %')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->color(fn (string $state): string => match (true) {
                        (float) $state >= 80 => 'success',
                        (float) $state >= 60 => 'warning',
                        default => 'danger',
                    })
                    ->sortable()
                    ->getStateUsing(function (Model $record) {
                        $totalVisits = $record->visits()->count();
                        if ($totalVisits === 0) return 0;

                        $visitedCount = $record->visits()->where('status', 'visited')->count();
                        return round(($visitedCount / $totalVisits) * 100, 2);
                    }),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->default(today()->startOfMonth())
                            ->maxDate(today()),
                        Forms\Components\DatePicker::make('to_date')
                            ->default(today())
                            ->maxDate(today()),
                    ]),
                Tables\Filters\SelectFilter::make('area_id')
                    ->label('Area')
                    ->options(function () {
                        return DB::table('areas')
                            ->join('area_user', 'areas.id', '=', 'area_user.area_id')
                            ->join('users', 'area_user.user_id', '=', 'users.id')
                            ->whereIn('users.id', GetMineScope::getUserIds())
                            ->pluck('areas.name', 'areas.id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('brick_id')
                    ->label('Brick')
                    ->options(function () {
                        return DB::table('bricks')->pluck('name', 'id')->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('grade')
                    ->label('Grade')
                    ->options(['A' => 'A', 'B' => 'B', 'C' => 'C', 'N' => 'N', 'PH' => 'PH'])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('client_type_id')
                    ->label('Client Type')
                    ->options(function () {
                        return DB::table('client_types')->pluck('name', 'id')->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('coverage_status')
                    ->label('Coverage Status')
                    ->options([
                        'high' => 'High Coverage (≥80%)',
                        'medium' => 'Medium Coverage (60-79%)',
                        'low' => 'Low Coverage (<60%)',
                        'no_visits' => 'No Visits',
                    ])
                    ->multiple(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->paginated([25, 50, 100, 250, 500, 'all'])
            ->defaultSort('name', 'asc')
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $tableFilters = request()->get('tableFilters', []);
        $dateRange = $tableFilters['date_range'] ?? [];

        $fromDate = isset($dateRange['from_date']) && !empty($dateRange['from_date'])
            ? $dateRange['from_date']
            : today()->startOfMonth()->toDateString();

        $toDate = isset($dateRange['to_date']) && !empty($dateRange['to_date'])
            ? $dateRange['to_date']
            : today()->toDateString();

        $query = Client::query()
            ->with(['area', 'brick', 'client_type', 'visits' => function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('visit_date', [$fromDate, $toDate]);
            }])
            ->whereHas('visits', function ($q) use ($fromDate, $toDate) {
                $q->whereBetween('visit_date', [$fromDate, $toDate])
                  ->whereIn('user_id', GetMineScope::getUserIds());
            });

        // Apply area filter
        if (isset($tableFilters['area_id']) && !empty($tableFilters['area_id'])) {
            $query->whereIn('area_id', $tableFilters['area_id']);
        }

        // Apply brick filter
        if (isset($tableFilters['brick_id']) && !empty($tableFilters['brick_id'])) {
            $query->whereIn('brick_id', $tableFilters['brick_id']);
        }

        // Apply grade filter
        if (isset($tableFilters['grade']) && !empty($tableFilters['grade'])) {
            $query->whereIn('grade', $tableFilters['grade']);
        }

        // Apply client type filter
        if (isset($tableFilters['client_type_id']) && !empty($tableFilters['client_type_id'])) {
            $query->whereIn('client_type_id', $tableFilters['client_type_id']);
        }

        // Apply coverage status filter
        if (isset($tableFilters['coverage_status']) && !empty($tableFilters['coverage_status'])) {
            $query->where(function ($q) use ($tableFilters, $fromDate, $toDate) {
                foreach ($tableFilters['coverage_status'] as $status) {
                    switch ($status) {
                        case 'high':
                            $q->orWhereRaw('(
                                SELECT COUNT(*) FROM visits
                                WHERE visits.client_id = clients.id
                                AND visits.visit_date BETWEEN ? AND ?
                                AND visits.user_id IN (?)
                            ) > 0 AND (
                                SELECT ROUND((COUNT(CASE WHEN status = "visited" THEN 1 END) / COUNT(*)) * 100, 2)
                                FROM visits
                                WHERE visits.client_id = clients.id
                                AND visits.visit_date BETWEEN ? AND ?
                                AND visits.user_id IN (?)
                            ) >= 80', [
                                $fromDate, $toDate, GetMineScope::getUserIds(),
                                $fromDate, $toDate, GetMineScope::getUserIds()
                            ]);
                            break;
                        case 'medium':
                            $q->orWhereRaw('(
                                SELECT COUNT(*) FROM visits
                                WHERE visits.client_id = clients.id
                                AND visits.visit_date BETWEEN ? AND ?
                                AND visits.user_id IN (?)
                            ) > 0 AND (
                                SELECT ROUND((COUNT(CASE WHEN status = "visited" THEN 1 END) / COUNT(*)) * 100, 2)
                                FROM visits
                                WHERE visits.client_id = clients.id
                                AND visits.visit_date BETWEEN ? AND ?
                                AND visits.user_id IN (?)
                            ) BETWEEN 60 AND 79', [
                                $fromDate, $toDate, GetMineScope::getUserIds(),
                                $fromDate, $toDate, GetMineScope::getUserIds()
                            ]);
                            break;
                        case 'low':
                            $q->orWhereRaw('(
                                SELECT COUNT(*) FROM visits
                                WHERE visits.client_id = clients.id
                                AND visits.visit_date BETWEEN ? AND ?
                                AND visits.user_id IN (?)
                            ) > 0 AND (
                                SELECT ROUND((COUNT(CASE WHEN status = "visited" THEN 1 END) / COUNT(*)) * 100, 2)
                                FROM visits
                                WHERE visits.client_id = clients.id
                                AND visits.visit_date BETWEEN ? AND ?
                                AND visits.user_id IN (?)
                            ) < 60', [
                                $fromDate, $toDate, GetMineScope::getUserIds(),
                                $fromDate, $toDate, GetMineScope::getUserIds()
                            ]);
                            break;
                        case 'no_visits':
                            $q->orWhereRaw('(
                                SELECT COUNT(*) FROM visits
                                WHERE visits.client_id = clients.id
                                AND visits.visit_date BETWEEN ? AND ?
                                AND visits.user_id IN (?)
                            ) = 0', [
                                $fromDate, $toDate, GetMineScope::getUserIds()
                            ]);
                            break;
                    }
                }
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientCoverageReports::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }
}
