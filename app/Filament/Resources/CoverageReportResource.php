<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoverageReportResource\Pages;
use App\Models\Reports\CoverageReportData;
use App\Models\User;
use App\Models\Area;
use App\Models\ClientType;
use App\Traits\ResourceHasPermission;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Exports\CoverageReportExport;
use Illuminate\Contracts\View\View;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class CoverageReportResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = User::class;
    protected static ?string $label = 'Medical Rep Coverage report';
    protected static ?string $navigationLabel = 'Coverage report';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';
    protected static ?string $permissionName = 'coverage-report';
    protected static ?string $slug = 'coverage-report';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('user_id')
                    ->label('ID'),
                TextColumn::make('name')
                    ->searchable()
                    ->label('Medical Rep'),
                TextColumn::make('area_name')
                    ->searchable()
                    ->label('Area'),
                TextColumn::make('working_days')
                    ->label('Working Days'),
                TextColumn::make('daily_visit_target')
                    ->label('Daily Visit Target'),
                TextColumn::make('office_work_count')
                    ->label('Office Work'),
                TextColumn::make('activities_count')
                    ->label('Activities'),
                TextColumn::make('actual_working_days')
                    ->label('Actual Working Days'),
                TextColumn::make('monthly_visit_target')
                    ->label('Monthly Visits Target'),
                TextColumn::make('sops')
                    ->label('SOPs %')
                    ->formatStateUsing(function ($state) {
                        return "{$state}%";
                    }),
                TextColumn::make('actual_visits')
                    ->label('Actual Visits'),
                TextColumn::make('call_rate')
                    ->label('Call Rate'),
                TextColumn::make('total_visits')
                    ->label('Total Visits'),
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
                Tables\Filters\SelectFilter::make('area')
                    ->label('Area')
                    ->options(Area::all()->pluck('name', 'id'))
                    ->multiple(),
                Tables\Filters\SelectFilter::make('grade')
                    ->label('Client Class')
                    ->options(['A' => 'A', 'B' => 'B', 'C' => 'C', 'N' => 'N', 'PH' => 'PH'])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('client_type_id')
                    ->label('Client Type')
                    ->options(ClientType::all()->pluck('name', 'id'))
                    ->multiple(),
            ])
            ->paginated([10, 25, 50, 100, 1000, 'all'])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('visit_breakdown')
                    ->label('Visit Breakdown')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->color('primary')
                    ->url(function (Model $record, Table $table): string {
                        // Get all current table filters
                        $dateFilter = $table->getFilter('date_range')?->getState() ?? [];
                        $areaFilter = $table->getFilter('area')?->getState() ?? null;
                        $gradeFilter = $table->getFilter('grade')?->getState() ?? null;
                        $clientTypeFilter = $table->getFilter('client_type_id')?->getState() ?? null;
                        
                        $fromDate = $dateFilter['from_date'] ?? now()->startOfMonth();
                        $toDate = $dateFilter['to_date'] ?? now()->endOfMonth();

                        $params = [
                            'user_id' => $record->user_id,
                            'from_date' => Carbon::parse($fromDate)->format('Y-m-d'),
                            'to_date' => Carbon::parse($toDate)->format('Y-m-d'),
                            'strategy' => 'coverage',
                        ];

                        // Add additional filters if they exist
                        if ($areaFilter) {
                            $params['area'] = is_array($areaFilter) ? implode(',', $areaFilter) : $areaFilter;
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
            ->bulkActions([
                Tables\Actions\BulkAction::make('export')
                    ->label('Export Selected')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function ($records) {
                        return (new CoverageReportExport($records->toQuery()))->download('coverage-report.xlsx');
                    }),
            ])
            ->headerActions([
                Tables\Actions\Action::make('export')
                    ->label('Export All')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->action(function () {
                        return (new CoverageReportExport(query: self::getEloquentQuery()))->download('coverage-report.xlsx');
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $dateRange = request()->get('tableFilters')['date_range'] ?? [];
        $fromDate = $dateRange['from_date'] ?? today()->startOfMonth();
        $toDate = $dateRange['to_date'] ?? today()->endOfMonth();

        $filters = [
            'area' => request()->get('tableFilters')['area'] ?? null,
            'grade' => request()->get('tableFilters')['grade'] ?? null,
            'client_type_id' => request()->get('tableFilters')['client_type_id'] ?? null,
        ];

        return CoverageReportData::getAggregatedQuery($fromDate, $toDate, $filters);
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
}
