<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoverageReportResource\Pages;
use App\Traits\ResourceHasPermission;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Support\Facades\DB;
use App\Models\Scopes\GetMineScope;
use App\Exports\CoverageReportExport;
use Filament\Tables\Actions\Action;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class CoverageReportResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = \App\Models\CoverageReportRow::class;
    protected static ?string $label = 'Coverage Report';
    protected static ?string $navigationLabel = 'Coverage Report';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';
    protected static ?string $slug = 'coverage-report-old';
    protected static ?string $permissionName = 'coverage-report';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('name')
                    ->label('User Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('area_name')
                    ->label('Area')
                    ->searchable()
                    ->limit(150)
                    ->sortable(),
                TextColumn::make('working_days')
                    ->label('Total Working Days')
                    ->numeric()
                    ->sortable(),
                    // ->description('Total distinct dates with any visits'),
                TextColumn::make('daily_visit_target')
                    ->label('Daily Visit Target')
                    ->numeric()
                    ->sortable(),
                    // ->description('Default: 8 visits per day'),
                TextColumn::make('monthly_visit_target')
                    ->label('Monthly Visit Target')
                    ->numeric()
                    ->sortable(),
                    // ->description('Daily target × Total working days'),
                TextColumn::make('office_work_count')
                    ->label('Office Work Count')
                    ->numeric()
                    ->sortable(),
                    // ->description('Visits with office_work status'),
                TextColumn::make('activities_count')
                    ->label('Activities Count')
                    ->numeric()
                    ->sortable(),
                    // ->description('Visits with activity status'),
                TextColumn::make('actual_working_days')
                    ->label('Actual Working Days')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('sops')
                    ->label('SOPS')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->sortable(),
                    // ->description('Actual visits ÷ (Daily target × Actual working days)'),
                TextColumn::make('actual_visits')
                    ->label('Actual Visits')
                    ->numeric()
                    ->sortable(),
                    // ->description('Visits with visited status'),
                TextColumn::make('call_rate')
                    ->label('Call Rate')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->sortable(),
                    // ->description('Average success rate of visits'),
                TextColumn::make('total_visits')
                    ->label('Total Visits')
                    ->numeric()
                    ->sortable(),
                    // ->description('All visits regardless of status'),
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
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('User')
                    ->options(function () {
                        return DB::table('users')
                            ->whereIn('id', GetMineScope::getUserIds())
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('client_type_id')
                    ->label('Client Type')
                    ->options(function () {
                        return DB::table('client_types')->pluck('name', 'id')->toArray();
                    })
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->paginated([25, 50, 100, 250, 500, 'all'])
            ->defaultSort('name', 'asc')
            ->headerActions([
                Action::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        $query = static::getEloquentQuery();
                        return (new CoverageReportExport($query))->download('coverage_report_' . date('Y-m-d_H-i-s') . '.xlsx');
                    })
            ])
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

        $filters = [
            'user_id' => $tableFilters['user_id'] ?? null,
            'client_type_id' => $tableFilters['client_type_id'] ?? null,
        ];

        return self::buildCoverageReportQuery($fromDate, $toDate, $filters);
    }

    private static function buildCoverageReportQuery(string $fromDate, string $toDate, array $filters): Builder
    {
        $userIds = GetMineScope::getUserIds();

        if (empty($userIds)) {
            $userIds = DB::table('users')->pluck('id')->toArray();
        }

        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $userIds = array_intersect($userIds, $filters['user_id']);
        }

        // Build filter strings for SQL functions
        $clientTypeFilter = null;

        if (isset($filters['client_type_id']) && !empty($filters['client_type_id'])) {
            $clientTypeFilter = implode(',', $filters['client_type_id']);
        }

        // Use raw query to avoid triggering model accessors
        $userIdsStr = empty($userIds) ? '0' : implode(',', $userIds);
        $actualVisitsClause = self::buildVisitSelectClause($fromDate, $toDate, $clientTypeFilter);
        $totalVisitsClause = self::buildTotalVisitSelectClause($fromDate, $toDate, $clientTypeFilter);

        // Build SQL query using our custom functions
        $sql = "
            SELECT
                u.id,
                u.name,
                COALESCE(GROUP_CONCAT(DISTINCT a.name SEPARATOR ', '), 'No Area') as area_name,
                get_working_calendar_days('{$fromDate}', '{$toDate}') as working_days,
                8 as daily_visit_target,
                get_working_calendar_days('{$fromDate}', '{$toDate}') * 8 as monthly_visit_target,
                (
                    SELECT COUNT(*)
                    FROM office_works ow
                    WHERE ow.user_id = u.id
                      AND DATE(ow.time_from) BETWEEN '{$fromDate}' AND '{$toDate}'
                ) as office_work_count,
                (
                    SELECT COUNT(*)
                    FROM activities act
                    WHERE act.user_id = u.id
                      AND DATE(act.date) BETWEEN '{$fromDate}' AND '{$toDate}'
                ) as activities_count,
                get_user_actual_working_days(u.id, '{$fromDate}', '{$toDate}') as actual_working_days,
                {$actualVisitsClause} as actual_visits,
                get_user_call_rate(u.id, '{$fromDate}', '{$toDate}') as call_rate,
                get_user_sops(u.id, '{$fromDate}', '{$toDate}', 8) as sops,
                {$totalVisitsClause} as total_visits
            FROM users u
            LEFT JOIN area_user au ON u.id = au.user_id
            LEFT JOIN areas a ON au.area_id = a.id
            WHERE u.id IN ({$userIdsStr})
            GROUP BY u.id, u.name
            HAVING {$totalVisitsClause} > 0
        ";

        // Return the query as Eloquent builder using fromSub with our DTO model
        return \App\Models\CoverageReportRow::query()
            ->fromSub($sql, 'coverage_report');
    }

    private static function buildVisitSelectClause(string $fromDate, string $toDate, ?string $clientTypeFilter): string
    {
        if ($clientTypeFilter) {
            $joins = " JOIN clients c ON v.client_id = c.id ";
            $conditions = [];

            $conditions[] = "c.client_type_id IN ({$clientTypeFilter})";

            $whereConditions = !empty($conditions) ? " AND " . implode(' AND ', $conditions) : "";

            return "(
                SELECT COUNT(*)
                FROM visits v
                {$joins}
                WHERE (v.user_id = u.id OR v.second_user_id = u.id)
                  AND v.status = 'visited'
                  AND DATE(v.visit_date) BETWEEN '{$fromDate}' AND '{$toDate}'
                  AND v.deleted_at IS NULL
                  {$whereConditions}
            )";
        } else {
            return "get_user_actual_visits(u.id, '{$fromDate}', '{$toDate}')";
        }
    }

    private static function buildTotalVisitSelectClause(string $fromDate, string $toDate, ?string $clientTypeFilter): string
    {
        if ($clientTypeFilter) {
            $joins = " JOIN clients c ON v.client_id = c.id ";
            $conditions = [];

            $conditions[] = "c.client_type_id IN ({$clientTypeFilter})";

            $whereConditions = !empty($conditions) ? " AND " . implode(' AND ', $conditions) : "";

            return "(
                SELECT COUNT(*)
                FROM visits v
                {$joins}
                WHERE (v.user_id = u.id OR v.second_user_id = u.id)
                  AND DATE(v.visit_date) BETWEEN '{$fromDate}' AND '{$toDate}'
                  AND v.deleted_at IS NULL
                  {$whereConditions}
            )";
        } else {
            return "get_user_total_visits(u.id, '{$fromDate}', '{$toDate}')";
        }
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCoverageReports::route('/'),
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
