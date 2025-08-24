<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientBreakdownResource\Pages;
use App\Models\Client;
use App\Models\UserBricksView;
use App\Models\Visit;
use App\Traits\ResourceHasPermission;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\User;

class ClientBreakdownResource extends Resource
{
    // use ResourceHasPermission;

    protected static ?string $model = Client::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Client Breakdown';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?int $navigationSort = 999; // High number to place at end
    protected static bool $shouldRegisterNavigation = false;
    // protected static ?string $slug = 'reports/breakdown/clients';

    public static function getRecordRouteKeyName(): string|null {
        return 'id';
    }

    // Dynamic title based on status
    public static function getModelLabel(): string
    {
        $status = request()->get('status', 'all');

        $statusLabel = match($status) {
            'visited' => 'Visited',
            'pending' => 'Pending',
            'cancelled' => 'Missed',
            'unvisited' => 'Unvisited',
            default => 'All'
        };

        return "Client Breakdown - {$statusLabel} Clients";
    }

    public static function getNavigationDescription(): string
    {
        $fromDate = request()->get('from_date', today()->firstOfMonth()->toDateString());
        $toDate = request()->get('to_date', today()->toDateString());
        $userId = request()->get('user_id');

        $userName = $userId ? User::find($userId)?->name : 'Unknown User';

        return "Date Range: {$fromDate} to {$toDate} | User: {$userName}";
    }


    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('Client ID')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name_en')
                    ->label('Client Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('brick.name')
                    ->label('Brick')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('shift')
                    ->label('Shift')
                    ->sortable()
                    ->searchable()
                    ->badge(),
                // TextColumn::make('clientType.name')
                //     ->label('Client Type')
                //     ->sortable()
                //     ->searchable(),
                TextColumn::make('visits_count')
                    ->label('Visited')
                    ->numeric()
                    ->color(fn (int $state): string => match (true) {
                        $state > 0 => 'success',
                        default => 'danger',
                    }),
                    // ->sortable()
                    // ->action(
                    //     Action::make('viewVisitedVisits')
                    //         ->label('View Visited Visits')
                    //         ->url(fn ($record) => self::buildVisitBreakdownUrl($record, 'visited'))
                    //         ->openUrlInNewTab()
                    // ),
                // TextColumn::make('pending_visits_count')
                //     ->label('Pending')
                //     ->numeric()
                //     ->color('warning')
                //     ->sortable()
                //     ->action(
                //         Action::make('viewPendingVisits')
                //             ->label('View Pending Visits')
                //             ->url(fn ($record) => self::buildVisitBreakdownUrl($record, 'pending'))
                //             ->openUrlInNewTab()
                //     ),
                // TextColumn::make('missed_visits_count')
                //     ->label('Missed')
                //     ->numeric()
                //     ->color('danger')
                //     ->sortable()
                //     ->action(
                //         Action::make('viewMissedVisits')
                //             ->label('View Missed Visits')
                //             ->url(fn ($record) => self::buildVisitBreakdownUrl($record, 'cancelled'))
                //             ->openUrlInNewTab()
                //     ),
                // TextColumn::make('total_visits_count')
                //     ->label('Total')
                //     ->numeric()
                //     ->color('info')
                //     ->sortable()
                //     ->action(
                //         Action::make('viewAllVisits')
                //             ->label('View All Visits')
                //             ->url(fn ($record) => self::buildVisitBreakdownUrl($record, 'all'))
                //             ->openUrlInNewTab()
                //     ),
                // TextColumn::make('achievement_percentage')
                //     ->label('Achievement %')
                //     ->numeric(
                //         decimalPlaces: 2,
                //         decimalSeparator: '.',
                //         thousandsSeparator: ',',
                //     )
                //     ->color(fn (string $state): string => match (true) {
                //         (float) $state >= 80 => 'success',
                //         (float) $state >= 60 => 'warning',
                //         default => 'danger',
                //     })
                //     ->sortable(),
            ])
            ->filters([
                // No filters as requested
            ])
            ->actions([
                // breakdown of done visits
                Action::make('breakdownVisits')
                    ->label('Breakdown Visits')
                    ->url(fn ($record) => self::buildVisitBreakdownUrl($record, 'visited'))
                    ->openUrlInNewTab()
            ])
            ->bulkActions([
                // No bulk actions as requested
            ])
            ->defaultPaginationPageOption(100)
            ->paginationPageOptions([100, 250, 500]);
    }

    public static function getEloquentQuery(): Builder
    {
        $userId = request()->get('user_id');

        if (!$userId) {
            // Return empty query if no user_id provided
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        $myUsers = User::descendantsAndSelf(auth()->user())->pluck('id')->toArray();
        if (!in_array($userId, $myUsers)) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        $status = request()->get('status', 'all');

        $dateFrom = request()->get('from_date', today()->firstOfMonth()->toDateString());
        $dateTo = request()->get('to_date', today()->toDateString());
        $bricksId = UserBricksView::getUserBrickIds($userId);

        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'user_id' => $userId,
            'bricks_id' => $bricksId,
        ];

        match($status) {
            'visited' => $query = self::getVisitedClientsQuery($filters),
            'unvisited' => $query = self::getUnvisitedClientsQuery($filters),
            'all' => $query = self::getAllClientsQuery($filters),
        };

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClientBreakdowns::route('/'),
        ];
    }

    /**
     * Build URL for visit breakdown similar to SOPsAndCallRate::buildBreakdownUrl
     */
    protected static function buildVisitBreakdownUrl(Client $record, string $status = 'all'): string
    {
        $fromDate = request()->get('from_date', today()->firstOfMonth()->toDateString());
        $toDate = request()->get('to_date', today()->toDateString());
        $userId = request()->get('user_id');

        $tableFilters = [
            'visit_date' => [
                'from_date' => $fromDate,
                'to_date' => $toDate
            ]
        ];

        // Add status filter if not 'all'
        if ($status !== 'all') {
            $tableFilters['status'] = [
                'value' => $status
            ];
        }

        $params = [
            'breakdown' => 'true',
            'tableFilters' => $tableFilters
        ];

        // Add user filter if provided
        if ($userId) {
            $params['user_id'] = [$userId];
        }

        // Add client filter
        $params['client_id'] = $record->id;

        return route('filament.admin.resources.visits.index', $params);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getVisitedClientsQuery($filters)
    {
        $query = Client::query()
            ->whereIn('brick_id', $filters['bricks_id'])
            ->where('active', true);

        $query->whereHas('visits', function ($query) use ($filters) {
            $query->where('status', 'visited')
                ->where('user_id', $filters['user_id'])
                ->whereBetween(DB::raw('DATE(visit_date)'), [$filters['date_from'], $filters['date_to']])
                ->whereNull('deleted_at');
        });

        $query->withCount(['visits' => function ($query) use ($filters) {
            $query->where('status', 'visited')
                ->where('user_id', $filters['user_id'])
                ->whereBetween(DB::raw('DATE(visit_date)'), [$filters['date_from'], $filters['date_to']])
                ->whereNull('deleted_at');
        }]);

        $query->with(['brick']);
        $query->orderBy('visits_count', 'desc');

        return $query;
    }

    public static function getUnvisitedClientsQuery($filters)
    {
        $query = Client::query();

        // Filter by user's bricks and active status
        $query->whereIn('clients.brick_id', $filters['bricks_id']);
        $query->where('clients.active', true);

        // Exclude clients that have visited status visits in the date range
        $query->whereNotExists(function ($subQuery) use ($filters) {
            $subQuery->select(DB::raw(1))
                ->from('visits')
                ->whereColumn('visits.client_id', 'clients.id')
                ->where('visits.status', 'visited')
                ->where('visits.user_id', $filters['user_id'])
                ->whereBetween(DB::raw('DATE(visits.visit_date)'), [$filters['date_from'], $filters['date_to']])
                ->whereNull('visits.deleted_at');
        });

        $query->select([
            'clients.*',
            DB::raw('0 as visits_count'),
        ]);

        $query->with(['brick']);
        // $query->groupBy('clients.id');
        $query->orderBy('visits_count', 'desc');

        return $query;
    }

    public static function getAllClientsQuery($filters)
    {
        $userId = $filters['user_id'];
        $bricksId = $filters['bricks_id'];
        $dateFrom = $filters['date_from'];
        $dateTo = $filters['date_to'];

        $query = Client::query();
        $query->whereIn('brick_id', $bricksId);
        $query->where('active', true);
        $query->leftJoin('visits', 'clients.id', '=', 'visits.client_id');

        $query->select([
            'clients.*',
            DB::raw('(
                SELECT COUNT(*)
                FROM visits v
                WHERE v.client_id = clients.id
                  AND v.status = "visited"
                  AND v.user_id = ?
                  AND DATE(v.visit_date) BETWEEN ? AND ?
                  AND v.deleted_at IS NULL
            ) as visits_count'),
        ]);
        $query->with(['brick']);
        $query->groupBy('clients.id');
        $query->orderBy('visits_count', 'desc');

        $query->addBinding([$userId], 'select');
        $query->addBinding([$filters['date_from'], $filters['date_to']], 'select');

        return $query;
    }
}
