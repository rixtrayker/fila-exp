<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VacationsReportResource\Pages;
use App\Models\User;
use App\Models\VacationRequest;
use App\Traits\ResouerceHasPermission;
use Faker\Provider\ar_EG\Text;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class VacationsReportResource extends Resource
{
    use ResouerceHasPermission;
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
                    ->label('Spent Days'),
                TextColumn::make('remaning_days')
                    ->state(fn($record) => 21 - $record->spent_days)
                    ->label('Remaining Days'),

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
                            fn (Builder $query, $date): Builder => $query->whereDate('vd.start', '>=', $date)
                        )
                        ->when(
                            $data['to_date'],
                            fn (Builder $query, $date): Builder => $query->whereDate('vd.end', '<=', $date));
                }),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Medical Rep')
                    ->multiple()
                    ->options(self::getMedicalReps())
                    ->query(function (Builder $query, array $data): Builder {
                        if(count($data['values'])){
                            return $query->whereIn('vacation_requests.user_id', $data['values']);
                        }
                        else
                            return $query;
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
            'users.name as medical_rep',
            'vacation_requests.approved as approved',
            DB::raw('GROUP_CONCAT( vacation_types.name SEPARATOR ", ") AS vacation_types'),
            DB::raw('SUM(
                case when vacation_requests.approved > 0 then
                    DATEDIFF(vd.end, vd.start) + IF(vd.start_shift = vd.end_shift, 0.5, 0) + IF(vd.start_shift = "AM" AND vd.end_shift = "PM", 1, 0)
                else 0
                end) as spent_days'))
            ->join('vacation_types', 'vacation_types.id', '=', 'vacation_requests.vacation_type_id')
            ->join('vacation_durations as vd', 'vacation_requests.id', '=', 'vd.vacation_request_id')
            ->join('users', 'users.id', '=', 'vacation_requests.user_id')
            ->groupBy('users.id');
    }

    public static function getRecordRouteKeyName(): string|null {
        return 'users.id';
    }

    private static function getMedicalReps(): array
    {
        if(self::$medicalReps)
            return self::$medicalReps;

        self::$medicalReps = User::getMine()->pluck('name','id')->toArray();
        return self::$medicalReps;
    }


    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVacationsReports::route('/'),
        ];
    }
}
