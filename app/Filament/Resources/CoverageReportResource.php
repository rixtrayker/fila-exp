<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoverageReportResource\Pages;
use App\Models\Reports\CoverageReportData;
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
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class CoverageReportResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = CoverageReportData::class;
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
                    // ->description('Distinct dates with visited status visits'),
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
                Tables\Filters\SelectFilter::make('area')
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
                Tables\Filters\SelectFilter::make('grade')
                    ->label('Client Grade')
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
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->paginated([25, 50, 100, 250, 500, 'all'])
            ->defaultSort('name', 'asc')
            ->bulkActions([]);
    }
    // use the getTableRecords instead of table query
    // public static function getTableRecords(): Collection | Paginator | CursorPaginator
    // {
    //     $query = self::getQuery();

    //     return $query->get();
    // }
    public static function getRecords(): Collection | Paginator | CursorPaginator
    {
        return self::getQuery()->get();
        // return $this->getLivewire()->getTableRecords();
    }

    // empty getEloquentQuery
    // public static function getEloquentQuery(): EloquentBuilder
    // {
    //     return new EloquentBuilder(self::getQuery());
    // }

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
            'area' => $tableFilters['area'] ?? null,
            'user_id' => $tableFilters['user_id'] ?? null,
            'grade' => $tableFilters['grade'] ?? null,
            'client_type_id' => $tableFilters['client_type_id'] ?? null,
        ];

        // Build custom query with correct calculations
        $userIds = GetMineScope::getUserIds();

        // If no user IDs from scope, use all users (for admin purposes)
        if (empty($userIds)) {
            $userIds = DB::table('users')->pluck('id')->toArray();
        }

        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $userIds = array_intersect($userIds, $filters['user_id']);
        }

        $query = DB::table('users')
            ->select([
                'users.id',
                'users.name',
                DB::raw('COALESCE(GROUP_CONCAT(DISTINCT areas.name), "") as area_name'),
                DB::raw('COUNT(DISTINCT DATE(visits.visit_date)) as working_days'),
                DB::raw('8 as daily_visit_target'), // Default daily target of 8
                DB::raw('COUNT(DISTINCT DATE(visits.visit_date)) * 8 as monthly_visit_target'),
                // activities and office work is wrong . . .. . .
                DB::raw('COUNT(CASE WHEN visits.status = "office_work" THEN 1 END) as office_work_count'),
                DB::raw('COUNT(CASE WHEN visits.status = "activity" THEN 1 END) as activities_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN visits.status = "visited" THEN DATE(visits.visit_date) END) as actual_working_days'),
                DB::raw('COUNT(CASE WHEN visits.status = "visited" THEN 1 END) as actual_visits'),
                // sops is actual visits / (8 * actual working days)
                // is multiplied by 100 then rounded to 2 decimal places
                DB::raw('CASE
                    WHEN COUNT(DISTINCT CASE WHEN visits.status = "visited" THEN DATE(visits.visit_date) END) > 0
                    THEN ROUND((COUNT(CASE WHEN visits.status = "visited" THEN 1 END) / (8 * COUNT(DISTINCT CASE WHEN visits.status = "visited" THEN DATE(visits.visit_date) END))) * 100, 2)
                    ELSE 0.00
                END as sops'),
                // call rate is how many visits per date so it's calculated: actual visits / working days
                // call rate = sum of actual visits / sum of actual working days
                DB::raw(value: 'ROUND(SUM(CASE WHEN visits.status = "visited" THEN 1 ELSE 0 END) / SUM(CASE WHEN visits.status = "visited" THEN 1 ELSE 0 END), 2) as call_rate'),
                DB::raw('COUNT(*) as total_visits'),
            ])
            ->leftJoin('area_user', 'users.id', '=', 'area_user.user_id')
            ->leftJoin('areas', 'area_user.area_id', '=', 'areas.id')
            ->leftJoin('visits', function ($join) use ($fromDate, $toDate, $filters) {
                $join->on('users.id', '=', 'visits.user_id')
                     ->whereBetween('visits.visit_date', [$fromDate, $toDate])
                     ->whereNull('visits.deleted_at');

                // Apply grade filter if specified
                if (isset($filters['grade']) && !empty($filters['grade'])) {
                    $join->join('clients', 'visits.client_id', '=', 'clients.id')
                         ->whereIn('clients.grade', $filters['grade']);
                }

                // Apply client type filter if specified
                if (isset($filters['client_type_id']) && !empty($filters['client_type_id'])) {
                    if (!isset($filters['grade'])) {
                        $join->join('clients', 'visits.client_id', '=', 'clients.id');
                    }
                    $join->whereIn('clients.client_type_id', $filters['client_type_id']);
                }
            })
            ->whereIn('users.id', $userIds)
            ->groupBy('users.id', 'users.name');

        // Apply area filter if specified
        if (isset($filters['area']) && !empty($filters['area'])) {
            $query->whereIn('areas.id', $filters['area']);
        }

        // Only include users with actual visits or office work
        $query->havingRaw('COUNT(*) > 0');

        return $query;
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
