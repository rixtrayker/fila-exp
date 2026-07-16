<?php

namespace App\Filament\Resources;

use App\Exports\AllActivitySOPsReportExport;
use App\Filament\Resources\AllActivitySOPsReportResource\Pages;
use App\Models\AllActivitySOPsReport;
use App\Models\Role;
use App\Models\Scopes\GetMineScope;
use App\Models\User;
use App\Services\AllActivitySOPsReportService;
use App\Traits\ResourceHasPermission;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AllActivitySOPsReportResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = AllActivitySOPsReport::class;

    protected static ?string $label = 'All Activity SOPs';

    protected static ?string $navigationLabel = 'All Activity SOPs Report';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $slug = 'all-activity-sops-report';

    protected static ?string $permissionName = 'all-activity-sops-report';

    public static function table(Table $table): Table
    {
        return $table
            ->columns(self::getColumns())
            ->filters(self::getFilters())
            ->filtersLayout(Tables\Enums\FiltersLayout::AboveContent)
            ->paginated(false)
            ->headerActions(self::getHeaderActions())
            ->actions([])
            ->bulkActions([]);
    }

    protected static function getColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label('User Name')
                ->searchable(),
            TextColumn::make('role_name')
                ->label('Role'),
            TextColumn::make('working_days')
                ->label('Working Days')
                ->numeric(),
            TextColumn::make('actual_working_days')
                ->label('Actual Working Days')
                ->numeric(),
            TextColumn::make('am_visits')
                ->label('AM Visits')
                ->numeric(),
            TextColumn::make('am_monthly_target')
                ->label('AM Target')
                ->numeric(decimalPlaces: 2),
            TextColumn::make('am_sops')
                ->label('AM SOPs %')
                ->numeric(decimalPlaces: 2),
            TextColumn::make('pm_visits')
                ->label('PM Visits')
                ->numeric(),
            TextColumn::make('pm_monthly_target')
                ->label('PM Target')
                ->numeric(decimalPlaces: 2),
            TextColumn::make('pm_sops')
                ->label('PM SOPs %')
                ->numeric(decimalPlaces: 2),
            TextColumn::make('ph_visits')
                ->label('PH Visits')
                ->numeric(),
            TextColumn::make('ph_monthly_target')
                ->label('PH Target')
                ->numeric(decimalPlaces: 2),
            TextColumn::make('ph_sops')
                ->label('PH SOPs %')
                ->numeric(decimalPlaces: 2),
            TextColumn::make('total_visits')
                ->label('Total Visits')
                ->numeric(),
            TextColumn::make('call_rate')
                ->label('Call Rate')
                ->numeric(decimalPlaces: 2),
        ];
    }

    protected static function getFilters(): array
    {
        return [
            Tables\Filters\SelectFilter::make('evaluation_role_id')
                ->label('Evaluation Role')
                ->options(fn () => Role::query()
                    ->whereIn('name', AllActivitySOPsReportService::EVALUATION_ROLES)
                    ->orderBy('name')
                    ->pluck('display_name', 'id')
                    ->toArray())
                ->default(fn () => Role::query()->where('name', 'medical-rep')->value('id'))
                ->selectablePlaceholder(false)
                ->searchable()
                ->preload(),
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
                ->label('Evaluated Users')
                ->options(function () {
                    return User::withoutGlobalScopes()
                        ->whereIn('id', GetMineScope::getUserIds())
                        ->whereHas('roles', fn ($query) => $query
                            ->whereIn('name', AllActivitySOPsReportService::EVALUATION_ROLES))
                        ->pluck('name', 'id')
                        ->toArray();
                })
                ->searchable()
                ->preload()
                ->multiple(),
        ];
    }

    protected static function getHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('export')
                ->label('Export to Excel')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action(function (ListRecords $livewire) {
                    $filters = $livewire->getTableFiltersForm()?->getState() ?? [];
                    $data = AllActivitySOPsReport::getReportDataWithFilters($filters);

                    $export = new AllActivitySOPsReportExport($data);

                    return $export->download($export->getFilename());
                }),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllActivitySOPsReports::route('/'),
        ];
    }
}
