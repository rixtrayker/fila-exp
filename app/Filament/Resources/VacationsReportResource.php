<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VacationsReportResource\Pages;
use App\Models\User;
use App\Models\VacationRequest;
use App\Services\VacationEntitlementService;
use App\Traits\ResourceHasPermission;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class VacationsReportResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = VacationRequest::class;

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static $medicalReps;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('medical_rep')
                    ->label('Medical Rep'),
                TextColumn::make('vacation_types')
                    ->label('Vacation Type'),
                TextColumn::make('spent_days')
                    ->label('Spent Days in Period')
                    ->state(function ($record, $livewire) {
                        [$fromDate, $toDate] = self::getSelectedDateRange($livewire);
                        $user = User::withoutGlobalScopes()->find($record->user_id);

                        return $user
                            ? app(VacationEntitlementService::class)
                                ->spentDaysInRange($user, $fromDate, $toDate)
                            : 0;
                    }),
                TextColumn::make('remaining_days')
                    ->state(function ($record) {
                        $user = User::withoutGlobalScopes()->find($record->user_id);

                        return $user
                            ? app(VacationEntitlementService::class)
                                ->remainingAnnualDays($user, now()->year)
                            : 0;
                    })
                    ->label('Remaining (This Year)'),

                // TextColumn::make('start_date')
                //     ->label('Start Date'),
            ])
            ->filters([
                Tables\Filters\Filter::make('dates_range')
                    ->form([
                        DatePicker::make('from_date')
                            ->default(today()->startOfQuarter()),
                        DatePicker::make('to_date')
                            ->default(today()->endOfQuarter()),
                    ])->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('vd.end', '>=', $date)
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('vd.start', '<=', $date));
                    }),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Medical Rep')
                    ->multiple()
                    ->options(self::getMedicalReps())
                    ->query(function (Builder $query, array $data): Builder {
                        if (count($data['values'])) {
                            return $query->whereIn('vacation_requests.user_id', $data['values']);
                        } else {
                            return $query;
                        }
                    }),
            ])
            ->actions([
            ])
            ->bulkActions([

            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        DB::statement("SET SESSION sql_mode=''");

        return VacationRequest::select(
            'users.id as id',
            'users.id as user_id',
            'users.name as medical_rep',
            'users.annual_vacation_days as annual_vacation_days',
            DB::raw('GROUP_CONCAT(DISTINCT vacation_types.name SEPARATOR ", ") AS vacation_types'),
            DB::raw('COUNT(DISTINCT CASE WHEN vacation_requests.approved > 0 THEN vacation_requests.id END) AS approved_count'),
            DB::raw('COUNT(DISTINCT CASE WHEN vacation_requests.approved = 0 THEN vacation_requests.id END) AS pending_count'),
            DB::raw('COUNT(DISTINCT CASE WHEN vacation_requests.approved < 0 THEN vacation_requests.id END) AS rejected_count'))
            ->join('vacation_types', 'vacation_types.id', '=', 'vacation_requests.vacation_type_id')
            ->join('vacation_durations as vd', 'vacation_requests.id', '=', 'vd.vacation_request_id')
            ->join('users', 'users.id', '=', 'vacation_requests.user_id')
            ->groupBy('users.id');
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'id';
    }

    private static function getMedicalReps(): array
    {
        if (self::$medicalReps) {
            return self::$medicalReps;
        }

        self::$medicalReps = User::allMine()->pluck('name', 'id')->toArray();

        return self::$medicalReps;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVacationsReports::route('/'),
        ];
    }

    public static function getSelectedDateRange($livewire): array
    {
        $state = $livewire->getTableFilterState('dates_range');

        return [
            Carbon::parse($state['from_date'] ?? today()->startOfQuarter()),
            Carbon::parse($state['to_date'] ?? today()->endOfQuarter()),
        ];
    }
}
