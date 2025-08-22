<?php

namespace App\Filament\Resources\CoverageReportResource\Tables;

use App\Exports\CoverageReportExport;
use App\Models\ClientType;
use App\Models\CoverageReport;
use App\Models\Scopes\GetMineScope;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CoverageReportTable
{
    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(25)
            ->columns(self::getColumns())
            ->filters(self::getFilters())
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->paginated([25, 50, 100, 250, 500, 'all'])
            ->defaultSort('name', 'asc')
            ->actions(self::getActions())
            ->headerActions(self::getHeaderActions())
            ->bulkActions([]);
    }

    /**
     * Get table columns configuration
     */
    protected static function getColumns(): array
    {
        return [
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
            TextColumn::make('daily_visit_target')
                ->label('Daily Visit Target')
                ->numeric()
                ->sortable(),
            TextColumn::make('monthly_visit_target')
                ->label('Monthly Visit Target')
                ->numeric()
                ->sortable(),
            TextColumn::make('office_work_count')
                ->label('Office Work Count')
                ->numeric()
                ->sortable(),
            TextColumn::make('activities_count')
                ->label('Activities Count')
                ->numeric()
                ->sortable(),
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
            TextColumn::make('actual_visits')
                ->label('Actual Visits')
                ->numeric()
                ->sortable(),
            TextColumn::make('call_rate')
                ->label('Call Rate')
                ->numeric(
                    decimalPlaces: 2,
                    decimalSeparator: '.',
                    thousandsSeparator: ',',
                )
                ->sortable(),
            TextColumn::make('total_visits')
                ->label('Total Visits')
                ->numeric()
                ->sortable(),
            TextColumn::make('vacation_days')
                ->label('Vacation Days')
                ->numeric(
                    decimalPlaces: 1,
                    decimalSeparator: '.',
                    thousandsSeparator: ',',
                )
                ->sortable(),
            TextColumn::make('daily_report_no')
                ->label('Daily Report No.')
                ->numeric()
                ->sortable(),
        ];
    }

    /**
     * Get table filters configuration
     */
    protected static function getFilters(): array
    {
        return [
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
                ->default(ClientType::PM),
        ];
    }

    /**
     * Get table actions configuration
     */
    protected static function getActions(): array
    {
        return [
            Tables\Actions\Action::make('visit_breakdown')
                ->label('Visit Breakdown')
                ->icon('heroicon-o-clipboard-document-list')
                ->color('primary')
                ->url(function (Model $record, Table $table): string {
                    return self::buildBreakdownUrl($record, $table);
                })
                ->openUrlInNewTab(),
        ];
    }

    /**
     * Get header actions configuration
     */
    protected static function getHeaderActions(): array
    {
        return [
            Action::make('export')
                ->label('Export to Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function () {
                    return self::handleExport();
                })
        ];
    }

    /**
     * Build breakdown URL for visit details
     */
    protected static function buildBreakdownUrl(Model $record, Table $table): string
    {
        $dateFilter = $table->getFilter('date_range')?->getState() ?? [];
        $userFilter = $table->getFilter('user_id')?->getState() ?? null;
        $clientTypeFilter = $table->getFilter('client_type_id')?->getState() ?? null;

        $fromDate = $dateFilter['from_date'] ?? now()->startOfMonth();
        $toDate = $dateFilter['to_date'] ?? now()->endOfMonth();

        $params = [
            'breakdown' => 'true',
            'user_id' => [$record->id],
            'tableFilters' => [
                'id' => [
                    'user_id' => [$record->id]
                ],
                'visit_date' => [
                    'from_date' => Carbon::parse($fromDate)->format('Y-m-d'),
                    'to_date' => Carbon::parse($toDate)->format('Y-m-d')
                ]
            ]
        ];

        return route('filament.admin.resources.visits.index', $params);
    }

    /**
     * Handle export action using the optimized CoverageReport model with flexible filters
     */
    protected static function handleExport()
    {
        $filtersState = request()->input('tableFilters', []);

        // Option 1: Use the flexible filter normalizer (recommended)
        $query = CoverageReport::getReportDataWithFilters($filtersState);

        // Option 2: Extract from URL if needed
        // $query = CoverageReport::getReportDataFromUrl();

        // Option 3: Manual filter extraction (if you need custom logic)
        /*
        $dateRange = $filtersState['date_range'] ?? [];
        $fromDate = $dateRange['from_date'] ?? today()->startOfMonth()->toDateString();
        $toDate = $dateRange['to_date'] ?? today()->toDateString();

        $selectedClientTypeId = $filtersState['client_type_id'] ?? ClientType::PM;
        if (is_array($selectedClientTypeId) && array_key_exists('values', $selectedClientTypeId)) {
            $selectedClientTypeId = $selectedClientTypeId['values'];
        }

        $filters = [
            'user_id' => $filtersState['user_id']['values'] ?? ($filtersState['user_id'] ?? null),
            'client_type_id' => $selectedClientTypeId,
        ];

        $query = CoverageReport::getReportData($fromDate, $toDate, $filters);
        */

        return (new CoverageReportExport($query))->download('coverage_report_' . date('Y-m-d_H-i-s') . '.xlsx');
    }
}
