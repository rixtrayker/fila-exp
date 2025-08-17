<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitPerformanceReportResource\Pages;
use App\Models\Visit;
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

class VisitPerformanceReportResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = Visit::class;
    protected static ?string $label = 'Visit Performance Report';
    protected static ?string $navigationLabel = 'Visit Performance';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $slug = 'visit-performance-report';
    protected static ?string $permissionName = 'visit-performance-report';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns([
                TextColumn::make('user.name')
                    ->label('Medical Representative')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.area.name')
                    ->label('Area')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.brick.name')
                    ->label('Brick')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.name')
                    ->label('Client Name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.grade')
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
                TextColumn::make('client.client_type.name')
                    ->label('Client Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('visit_date')
                    ->label('Visit Date')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'visited' => 'success',
                        'planned' => 'info',
                        'pending' => 'warning',
                        'missed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('visit_type')
                    ->label('Visit Type')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Notes')
                    ->limit(50)
                    ->searchable(),
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
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'visited' => 'Visited',
                        'planned' => 'Planned',
                        'pending' => 'Pending',
                        'missed' => 'Missed',
                    ])
                    ->multiple(),
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
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Medical Representative')
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
            ->defaultSort('visit_date', 'desc')
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

        $query = Visit::query()
            ->with(['user', 'client.area', 'client.brick', 'client.client_type'])
            ->whereBetween('visit_date', [$fromDate, $toDate])
            ->whereIn('user_id', GetMineScope::getUserIds());

        // Apply status filter
        if (isset($tableFilters['status']) && !empty($tableFilters['status'])) {
            $query->whereIn('status', $tableFilters['status']);
        }

        // Apply area filter
        if (isset($tableFilters['area_id']) && !empty($tableFilters['area_id'])) {
            $query->whereHas('client.area', function ($q) use ($tableFilters) {
                $q->whereIn('areas.id', $tableFilters['area_id']);
            });
        }

        // Apply user filter
        if (isset($tableFilters['user_id']) && !empty($tableFilters['user_id'])) {
            $query->whereIn('user_id', $tableFilters['user_id']);
        }

        // Apply grade filter
        if (isset($tableFilters['grade']) && !empty($tableFilters['grade'])) {
            $query->whereHas('client', function ($q) use ($tableFilters) {
                $q->whereIn('grade', $tableFilters['grade']);
            });
        }

        // Apply client type filter
        if (isset($tableFilters['client_type_id']) && !empty($tableFilters['client_type_id'])) {
            $query->whereHas('client', function ($q) use ($tableFilters) {
                $q->whereIn('client_type_id', $tableFilters['client_type_id']);
            });
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisitPerformanceReports::route('/'),
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
