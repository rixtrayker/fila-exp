<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FrequencyReportResource\Pages;
use App\Models\Brick;
use App\Models\Client;
use App\Models\ClientType;
use App\Models\Reports\FrequencyReportData;
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

class FrequencyReportResource extends Resource
{
    use ResourceHasPermission;
    protected static ?string $model = FrequencyReportData::class;
    protected static ?string $label = 'Frequency report';
    protected static ?string $navigationLabel = 'Frequency report';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $slug = 'frequency-report';
    protected static ?string $permissionName = 'frequency-report';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('client_id')
                    ->label('ID'),
                TextColumn::make('client_name')
                    ->searchable()
                    ->label('Name'),
                TextColumn::make('client_type_name')
                    ->label('Client Type'),
                TextColumn::make('grade')
                    ->label('Grade'),
                TextColumn::make('brick_name')
                    ->label('Brick'),
                // TextColumn::make('brick.area.name')
                    // ->label('Area'),
                TextColumn::make('done_visits_count')
                    ->color('success')
                    ->label('Done Visits'),
                TextColumn::make('pending_visits_count')
                    ->color('warning')
                    ->label('Planned & Pending Visits'),
                TextColumn::make('missed_visits_count')
                    ->color('danger')
                    ->label('Missed Visits'),
                TextColumn::make('total_visits_count')
                    ->color('info')
                    ->label('Total Visits'),
                TextColumn::make('achievement_percentage')
                    ->label('Achievement %'),
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
                    ->options(Brick::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('grade')
                    ->options(['A' => 'A', 'B' => 'B', 'C' => 'C', 'N' => 'N', 'PH' => 'PH']),
                Tables\Filters\SelectFilter::make('client_type_id')
                    ->label('Client Type')
                    ->options(ClientType::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->paginated([10, 25, 50, 100, 1000, 'all'])
            ->actions([
                Tables\Actions\Action::make('visit_breakdown')
                    ->label('Visit Breakdown')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('primary')
                    ->url(function (Model $record, Table $table): string {
                        // Get all current table filters
                        $dateFilter = $table->getFilter('date_range')?->getState() ?? [];
                        $brickFilter = $table->getFilter('brick_id')?->getState() ?? null;
                        $gradeFilter = $table->getFilter('grade')?->getState() ?? null;
                        $clientTypeFilter = $table->getFilter('client_type_id')?->getState() ?? null;

                        $fromDate = $dateFilter['from_date'] ?? now()->startOfMonth();
                        $toDate = $dateFilter['to_date'] ?? now()->endOfMonth();

                        $params = [
                            'client_id' => $record->id,
                            'from_date' => Carbon::parse($fromDate)->format('Y-m-d'),
                            'to_date' => Carbon::parse($toDate)->format('Y-m-d'),
                            'strategy' => 'frequency',
                        ];

                        // Add additional filters if they exist
                        if ($brickFilter) {
                            $params['brick_id'] = is_array($brickFilter) ? implode(',', $brickFilter) : $brickFilter;
                        }
                        if ($gradeFilter) {
                            $params['grade'] = is_array($gradeFilter) ? implode(',', $gradeFilter) : $gradeFilter;
                        }
                        if ($clientTypeFilter) {
                            $params['client_type_id'] = is_array($clientTypeFilter) ? implode(',', $clientTypeFilter) : $clientTypeFilter;
                        }

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
            'grade' => $tableFilters['grade'] ?? null,
            'client_type_id' => $tableFilters['client_type_id'] ?? null,
        ];

        return FrequencyReportData::getAggregatedQuery($fromDate, $toDate, $filters);
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
}
