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
                    ->modalContent(function (Model $record, Table $table): View {
                        $dateFilter = $table->getFilter('date_range')?->getState() ?? [];
                        $fromDate = $dateFilter['from_date'] ?? now()->startOfMonth();
                        $toDate = $dateFilter['to_date'] ?? now()->endOfMonth();

                        $visitData = CoverageReportData::getUserData($record->user_id, $fromDate, $toDate);
                        dd(1);
                        return view('filament.resources.coverage-report-resource.pages.components.visit-breakdown-modal', [
                            'fromDate' => Carbon::parse($fromDate)->format('M j, Y'),
                            'toDate' => Carbon::parse($toDate)->format('M j, Y'),
                            'visitData' => $visitData,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalWidth('4xl')
                    ->modalHeading(fn (User $record) => "Visit Breakdown - {$record->name}")
                    ->slideOver(),
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
