<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FrequencyReportResource\Pages;
use App\Models\FrequencyReportRow;
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

class FrequencyReportResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = FrequencyReportRow::class;
    protected static ?string $label = 'Frequency Report';
    protected static ?string $navigationLabel = 'Frequency Report';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $slug = 'frequency-report';
    protected static ?string $permissionName = 'frequency-report';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('client_name')
                    ->label('Client Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client_type_name')
                    ->label('Client Type')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('brick_name')
                    ->label('Brick')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('done_visits_count')
                    ->label('Done Visits')
                    ->numeric()
                    ->color('success')
                    ->sortable(),
                TextColumn::make('pending_visits_count')
                    ->label('Planned & Pending Visits')
                    ->numeric()
                    ->color('warning')
                    ->sortable(),
                TextColumn::make('missed_visits_count')
                    ->label('Missed Visits')
                    ->numeric()
                    ->color('danger')
                    ->sortable(),
                TextColumn::make('total_visits_count')
                    ->label('Total Visits')
                    ->numeric()
                    ->color('info')
                    ->sortable(),
                TextColumn::make('achievement_percentage')
                    ->label('Achievement %')
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
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('date_range')
                    ->label('Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->default(today()->subDays(7))
                            ->maxDate(today()),
                        Forms\Components\DatePicker::make('to_date')
                            ->default(today())
                            ->maxDate(today()),
                    ]),
                Tables\Filters\SelectFilter::make('brick_id')
                    ->label('Brick')
                    ->options(function () {
                        return DB::table('bricks')->pluck('name', 'id')->toArray();
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
            ->defaultSort('client_name', 'asc')
            ->actions([
                Tables\Actions\Action::make('visit_breakdown')
                    ->label('Visit Breakdown')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('primary')
                    ->url(function (Model $record, Table $table): string {
                        // Get all current table filters
                        $dateFilter = $table->getFilter('date_range')?->getState() ?? [];
                        $brickFilter = $table->getFilter('brick_id')?->getState() ?? null;
                        $clientTypeFilter = $table->getFilter('client_type_id')?->getState() ?? null;

                        $fromDate = $dateFilter['from_date'] ?? now()->startOfMonth();
                        $toDate = $dateFilter['to_date'] ?? now()->endOfMonth();

                        $params = [
                            'client_id' => $record->client_id,
                            'from_date' => Carbon::parse($fromDate)->format('Y-m-d'),
                            'to_date' => Carbon::parse($toDate)->format('Y-m-d'),
                            'strategy' => 'frequency',
                        ];

                        // Add additional filters if they exist
                        // if ($brickFilter) {
                        //     $params['brick_id'] = is_array($brickFilter) ? implode(',', $brickFilter) : $brickFilter;
                        // }

                        // if ($clientTypeFilter) {
                        //     $params['client_type_id'] = is_array($clientTypeFilter) ? implode(',', $clientTypeFilter) : $clientTypeFilter;
                        // }

                        return route('filament.admin.pages.visit-breakdown', $params);
                    })
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $tableFilters = request()->get('tableFilters', []);
        $dateRange = $tableFilters['date_range'] ?? [];

        $fromDate = isset($dateRange['from_date']) && !empty($dateRange['from_date'])
            ? $dateRange['from_date']
            : today()->subDays(7)->toDateString();

        $toDate = isset($dateRange['to_date']) && !empty($dateRange['to_date'])
            ? $dateRange['to_date']
            : today()->toDateString();

        $filters = [
            'brick_id' => $tableFilters['brick_id'] ?? null,
            'client_type_id' => $tableFilters['client_type_id'] ?? null,
        ];

        return self::buildFrequencyReportQuery($fromDate, $toDate, $filters);
    }

    private static function buildFrequencyReportQuery(string $fromDate, string $toDate, array $filters): Builder
    {
        // Build filter strings for SQL functions
        $brickFilter = null;
        $clientTypeFilter = null;

        if (isset($filters['brick_id']) && !empty($filters['brick_id'])) {
            $brickFilter = implode(',', $filters['brick_id']);
        }

        if (isset($filters['client_type_id']) && !empty($filters['client_type_id'])) {
            $clientTypeFilter = implode(',', $filters['client_type_id']);
        }

        // Build SQL query for frequency report
        $sql = "
            SELECT
                c.id as client_id,
                COALESCE(c.name_en, c.name_ar) as client_name,
                ct.name as client_type_name,
                b.name as brick_name,
                (
                    SELECT COUNT(*)
                    FROM visits v
                    WHERE v.client_id = c.id
                      AND v.status = 'visited'
                      AND DATE(v.visit_date) BETWEEN '{$fromDate}' AND '{$toDate}'
                      AND v.deleted_at IS NULL
                ) as done_visits_count,
                (
                    SELECT COUNT(*)
                    FROM visits v
                    WHERE v.client_id = c.id
                      AND v.status IN ('planned', 'pending')
                      AND DATE(v.visit_date) BETWEEN '{$fromDate}' AND '{$toDate}'
                      AND v.deleted_at IS NULL
                ) as pending_visits_count,
                (
                    SELECT COUNT(*)
                    FROM visits v
                    WHERE v.client_id = c.id
                      AND v.status = 'missed'
                      AND DATE(v.visit_date) BETWEEN '{$fromDate}' AND '{$toDate}'
                      AND v.deleted_at IS NULL
                ) as missed_visits_count,
                (
                    SELECT COUNT(*)
                    FROM visits v
                    WHERE v.client_id = c.id
                      AND DATE(v.visit_date) BETWEEN '{$fromDate}' AND '{$toDate}'
                      AND v.deleted_at IS NULL
                ) as total_visits_count,
                CASE
                    WHEN (
                        SELECT COUNT(*)
                        FROM visits v
                        WHERE v.client_id = c.id
                          AND DATE(v.visit_date) BETWEEN '{$fromDate}' AND '{$toDate}'
                          AND v.deleted_at IS NULL
                    ) > 0 THEN
                        ROUND(
                            (
                                SELECT COUNT(*)
                                FROM visits v
                                WHERE v.client_id = c.id
                                  AND v.status = 'visited'
                                  AND DATE(v.visit_date) BETWEEN '{$fromDate}' AND '{$toDate}'
                                  AND v.deleted_at IS NULL
                            ) * 100.0 /
                            (
                                SELECT COUNT(*)
                                FROM visits v
                                WHERE v.client_id = c.id
                                  AND DATE(v.visit_date) BETWEEN '{$fromDate}' AND '{$toDate}'
                                  AND v.deleted_at IS NULL
                            ), 2
                        )
                    ELSE 0
                END as achievement_percentage
            FROM clients c
            LEFT JOIN client_types ct ON c.client_type_id = ct.id
            LEFT JOIN bricks b ON c.brick_id = b.id
            WHERE c.active = 1
        ";

        // Add filters if they exist
        if ($brickFilter) {
            $sql .= " AND c.brick_id IN ({$brickFilter})";
        }

        if ($clientTypeFilter) {
            $sql .= " AND c.client_type_id IN ({$clientTypeFilter})";
        }

        $sql .= " HAVING total_visits_count > 0 AND client_id IS NOT NULL ORDER BY client_name ASC";

        // Return the query as Eloquent builder using fromSub with our DTO model
        return FrequencyReportRow::query()
            ->fromSub($sql, 'frequency_report');
    }

    public static function getRecordRouteKeyName(): string|null {
        return 'client_id';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFrequencyReports::route('/'),
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
