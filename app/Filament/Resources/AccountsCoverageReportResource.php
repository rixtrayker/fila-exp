<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountsCoverageReportResource\Pages;
use App\Models\User;
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
use App\Models\Brick;
use App\Models\Scopes\GetMineScope;

class AccountsCoverageReportResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = User::class;
    protected static ?string $label = 'Accounts Coverage Report';
    protected static ?string $navigationLabel = 'Accounts Coverage';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $slug = 'accounts-coverage-report';
    protected static ?string $permissionName = 'accounts-coverage-report';
    protected static bool $shouldRegisterNavigation = true;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(50)
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                    // ->sortable(),
                TextColumn::make('medical_rep_name')
                    ->label('Medical Rep')
                    ->searchable()
                    // ->sortable()
                    ->weight('semibold'),
                TextColumn::make('total_area_clients')
                    ->label('Clients (Total Area)')
                    ->numeric()
                    ->color('info')
                    // ->sortable()
                    ->url(fn ($record) => self::buildClientBreakdownUrl($record, 'all'))
                    ->openUrlInNewTab(),
                TextColumn::make('visited_doctors')
                    ->label('Visited Doctors')
                    ->numeric()
                    ->color('success')
                    // ->sortable()
                    ->url(fn ($record) => self::buildClientBreakdownUrl($record, 'visited'))
                    ->openUrlInNewTab(),
                TextColumn::make('unvisited_doctors')
                    ->label('Unvisited Doctors')
                    ->numeric()
                    ->color('danger')
                    // ->sortable()
                    ->url(fn ($record) => self::buildClientBreakdownUrl($record, 'unvisited'))
                    ->openUrlInNewTab(),
                TextColumn::make('coverage_percentage')
                    ->label('Coverage %')
                    ->numeric(
                        decimalPlaces: 2,
                        decimalSeparator: '.',
                        thousandsSeparator: ',',
                    )
                    ->suffix('%')
                    ->color(fn (string $state): string => match (true) {
                        (float) $state >= 80 => 'success',
                        (float) $state >= 60 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),
                TextColumn::make('actual_visits')
                    ->label('Actual Visits (Done)')
                    ->numeric()
                    ->color('primary')
                    // ->sortable()
                    ->url(fn ($record) => self::buildVisitBreakdownUrl($record))
                    ->openUrlInNewTab(),
                TextColumn::make('clinic_visits')
                    ->label('Clinic Visits')
                    ->numeric()
                    ->color('gray')
                    // ->sortable()
                    ->url(fn ($record) => self::buildClinicVisitBreakdownUrl($record)   )
                    ->openUrlInNewTab(),
            ])
            ->filters([
                // apply two pure filters for visit_date and status
                Tables\Filters\Filter::make('visit_date')
                    ->label('Visit Date')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->default(today()->firstOfMonth())
                            ->maxDate(today()),
                        Forms\Components\DatePicker::make('to_date')
                            ->default(today())
                            ->maxDate(today()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return self::buildAccountsCoverageReportQuery($data['from_date'], $data['to_date']);
                    }),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->paginated([50, 100, 250, 500, 'all'])
            ->defaultSort('coverage_percentage', 'desc')
            // ->actions([
            //     Tables\Actions\Action::make('view_details')
            //         ->label('View Details')
            //         ->icon('heroicon-o-eye')
            //         ->color('primary')
            //         ->url(function (Model $record, Table $table): string {
            //             // Get all current table filters
            //             $dateFilter = $table->getFilter('date_range')?->getState() ?? [];

            //             $fromDate = $dateFilter['from_date'] ?? now()->firstOfMonth();
            //             $toDate = $dateFilter['to_date'] ?? now();

            //             $params = [
            //                 'breakdown' => 'true',
            //                 'strategy' => 'accounts-coverage',
            //                 'tableFilters' => [
            //                     'visit_date' => [
            //                         'from_date' => Carbon::parse($fromDate)->format('Y-m-d'),
            //                         'to_date' => Carbon::parse($toDate)->format('Y-m-d')
            //                     ]
            //                 ]
            //             ];

            //             $params['user_id'] = $record->id;

            //             return route('filament.admin.pages.visit-breakdown', $params);
            //         })
            //         ->openUrlInNewTab(),
            // ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $tableFilters = request()->get('tableFilters', []);

        $fromDate = isset($tableFilters['from_date']) && !empty($tableFilters['from_date'])
            ? $tableFilters['from_date']
            : today()->firstOfMonth()->toDateString();

        $toDate = isset($tableFilters['to_date']) && !empty($tableFilters['to_date'])
            ? $tableFilters['to_date']
            : today()->toDateString();

        return self::buildAccountsCoverageReportQuery($fromDate, $toDate);
    }

    /**
     * Build the accounts coverage report query using the user_bricks_view
     * This view consolidates user brick access through direct assignments and area-based access
     *
     * @param string $fromDate
     * @param string $toDate
     * @return Builder
     */
    private static function buildAccountsCoverageReportQuery(string $fromDate, string $toDate): Builder
    {
        $userIds = GetMineScope::getUserIds();

        if (empty($userIds)) {
            // If no users accessible, return empty query
            return User::query()->whereRaw('1 = 0');
        }

        $userIdsStr = implode(',', $userIds);

        // Use DB::raw to create a query that Filament can work with
        return User::withoutGlobalScopes()
            ->select([
                'users.id',
                'users.name as medical_rep_name',
                DB::raw('COALESCE(area_clients.total_clients, 0) as total_area_clients'),
                DB::raw('COALESCE(visited_clients.visited_count, 0) as visited_doctors'),
                DB::raw('COALESCE(area_clients.total_clients, 0) - COALESCE(visited_clients.visited_count, 0) as unvisited_doctors'),
                DB::raw("
                    CASE
                        WHEN COALESCE(area_clients.total_clients, 0) > 0 THEN
                            ROUND((COALESCE(visited_clients.visited_count, 0) * 100.0) / area_clients.total_clients, 2)
                        ELSE 0
                    END as coverage_percentage
                "),
                DB::raw('COALESCE(actual_visits.visit_count, 0) as actual_visits'),
                DB::raw('COALESCE(clinic_visits.clinic_visit_count, 0) as clinic_visits')
            ])
            ->leftJoin(DB::raw("(
                SELECT
                    ubv.user_id,
                    COUNT(DISTINCT c.id) as total_clients
                FROM user_bricks_view ubv
                JOIN clients c ON ubv.brick_id = c.brick_id
                WHERE c.active = 1
                  AND ubv.user_id IN ({$userIdsStr})
                GROUP BY ubv.user_id
            ) as area_clients"), 'users.id', '=', 'area_clients.user_id')
            ->leftJoin(DB::raw("(
                SELECT
                    v.user_id,
                    COUNT(DISTINCT v.client_id) as visited_count
                FROM visits v
                JOIN clients c ON v.client_id = c.id
                JOIN user_bricks_view ubv ON c.brick_id = ubv.brick_id AND v.user_id = ubv.user_id
                WHERE v.status = 'visited'
                  AND DATE(v.visit_date) BETWEEN '{$fromDate}' AND '{$toDate}'
                  AND v.deleted_at IS NULL
                  AND c.active = 1
                  AND ubv.user_id IN ({$userIdsStr})
                GROUP BY v.user_id
            ) as visited_clients"), 'users.id', '=', 'visited_clients.user_id')
            ->leftJoin(DB::raw("(
                SELECT
                    v2.user_id,
                    COUNT(*) as visit_count
                FROM visits v2
                JOIN clients c2 ON v2.client_id = c2.id
                JOIN user_bricks_view ubv2 ON c2.brick_id = ubv2.brick_id AND v2.user_id = ubv2.user_id
                WHERE v2.status = 'visited'
                  AND DATE(v2.visit_date) BETWEEN '{$fromDate}' AND '{$toDate}'
                  AND v2.deleted_at IS NULL
                  AND c2.active = 1
                  AND ubv2.user_id IN ({$userIdsStr})
                GROUP BY v2.user_id
            ) as actual_visits"), 'users.id', '=', 'actual_visits.user_id')
            ->leftJoin(DB::raw("(
                SELECT
                    v3.user_id,
                    COUNT(*) as clinic_visit_count
                FROM visits v3
                JOIN clients c3 ON v3.client_id = c3.id
                JOIN user_bricks_view ubv3 ON c3.brick_id = ubv3.brick_id AND v3.user_id = ubv3.user_id
                WHERE v3.status = 'visited'
                  AND DATE(v3.visit_date) BETWEEN '{$fromDate}' AND '{$toDate}'
                  AND v3.deleted_at IS NULL
                  AND c3.client_type_id = 1
                  AND c3.active = 1
                  AND ubv3.user_id IN ({$userIdsStr})
                GROUP BY v3.user_id
            ) as clinic_visits"), 'users.id', '=', 'clinic_visits.user_id')
            ->where('users.is_active', 1)
            ->whereIn('users.id', $userIds)
            ->orderBy('coverage_percentage', 'desc')
            ->orderBy('medical_rep_name', 'asc');
    }

    public static function getRecordRouteKeyName(): string|null {
        return 'id';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAccountsCoverageReports::route('/'),
        ];
    }

    // canview and can view any are true
    public static function canViewAny(): bool
    {
        return true;
    }

    public static function canView(Model $record): bool
    {
        return true;
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

    /**
     * Build URL for client breakdown
     */
    protected static function buildClientBreakdownUrl(Model $record, string $status = 'all'): string
    {
        $tableFilters = request()->get('tableFilters', []);
        $dateRange = $tableFilters['date_range'] ?? [];

        $fromDate = isset($dateRange['from_date']) && !empty($dateRange['from_date'])
            ? $dateRange['from_date']
            : today()->firstOfMonth()->toDateString();

        $toDate = isset($dateRange['to_date']) && !empty($dateRange['to_date'])
            ? $dateRange['to_date']
            : today()->toDateString();

        $userId = $record->id;

        $params = [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'status' => $status,
            'user_id' => $userId
        ];

        $url = route('filament.admin.resources.client-breakdowns.index', $params);
        return $url;
    }

    /**
     * Build URL for visit breakdown
     */
    protected static function buildVisitBreakdownUrl(Model $record): string
    {
        $tableFilters = request()->get('tableFilters', []);
        $dateRange = $tableFilters['date_range'] ?? [];

        $fromDate = isset($dateRange['from_date']) && !empty($dateRange['from_date'])
            ? $dateRange['from_date']
            : today()->firstOfMonth()->toDateString();

        $toDate = isset($dateRange['to_date']) && !empty($dateRange['to_date'])
            ? $dateRange['to_date']
            : today()->toDateString();

        // Get the user_id from the record
        $userId = $record->id;

        $params = [
            'breakdown' => 'true',
            'user_id' => [$userId],
            'tableFilters' => [
                'visit_date' => [
                    'from_date' => $fromDate,
                    'to_date' => $toDate
                ],
                'status' => [
                    'value' => 'visited'
                ]
            ]
        ];

        return route('filament.admin.resources.visits.index', $params);
    }

    /**
     * Build URL for clinic visit breakdown
     */
    protected static function buildClinicVisitBreakdownUrl(Model $record): string
    {
        $tableFilters = request()->get('tableFilters', []);
        $dateRange = $tableFilters['date_range'] ?? [];

        $fromDate = isset($dateRange['from_date']) && !empty($dateRange['from_date'])
            ? $dateRange['from_date']
            : today()->firstOfMonth()->toDateString();

        $toDate = isset($dateRange['to_date']) && !empty($dateRange['to_date'])
            ? $dateRange['to_date']
            : today()->toDateString();

        // Get the user_id from the record
        $userId = $record->id;

        $params = [
            'breakdown' => 'true',
            'user_id' => [$userId],

            'tableFilters' => [
                'client_type_id' => [
                    'value' => [1]
                ],
                'visit_date' => [
                    'from_date' => $fromDate,
                    'to_date' => $toDate
                ],
                'status' => [
                    'value' => 'visited'
                ]
            ]
        ];

        return route('filament.admin.resources.visits.index', $params);
    }
}
