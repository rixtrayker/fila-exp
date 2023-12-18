<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FrequencyReportResource\Pages;
use App\Models\Brick;
use App\Models\Client;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class FrequencyReportResource extends Resource
{
    protected static ?string $model = Client::class;
    protected static ?string $label = 'Frequency report';

    protected static ?string $navigationLabel = 'Frequency report';
    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $slug = 'frequency-report';

    // protected static bool $shouldRegisterNavigation = false;
    protected static $avgGrade;
    protected static $bricks;
    protected static $medicalReps;


    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('name_en')
                    ->searchable()
                    ->label('Name'),
                TextColumn::make('done_visits_count')
                    ->label('Done Visits'),
                TextColumn::make('pending_visits_count')
                    ->label('Planned & Pending Visits'),
                TextColumn::make('missed_visits_count')
                    ->label('Missed Visits'),
                TextColumn::make('total_visits_count')
                    ->label('Total Visits'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('brick_id')
                    ->label('Brick')
                    ->relationship('brick', 'name')
                    ->searchable()
                    ->multiple()
                    ->options(fn()=>self::getBricks()),
                Tables\Filters\SelectFilter::make('grade')
                    ->options(fn()=>self::gradeAVG()),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Medical Rep')
                    ->multiple()
                    ->options(self::getMedicalReps())
                    // ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    // ->preload()
                    ->query(function (Builder $query, array $data): Builder {
                        if(count($data['values'])){
                            return $query->rightJoin('visits', 'clients.id', '=', 'visits.client_id')
                                ->whereIn('visits.user_id', $data['values']);
                        }
                        else
                            return $query;
                        }),
                Tables\Filters\Filter::make('visit_date')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->default(today()->subDays(7)),
                        Forms\Components\DatePicker::make('to_date')
                            ->default(today()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visits.visit_date', '>=', $date)
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visits.visit_date', '<=', $date)
                            );
                    })
            ])
            ->paginated([10, 25, 50, 100, 1000, 'all'])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        DB::statement("SET SESSION sql_mode=''");

        return Client::select('clients.id as id',
        'name_en',
        'clients.brick_id as brick_id',
        'clients.grade as grade',
        )
            ->selectRaw('SUM(CASE WHEN visits.status = "visited" THEN 1 ELSE 0 END) AS done_visits_count')
            ->selectRaw('IFNULL(SUM(CASE WHEN visits.status IN ("pending", "planned") THEN 1 ELSE 0 END), 0) AS pending_visits_count')
            ->selectRaw('SUM(CASE WHEN visits.status = "cancelled" THEN 1 ELSE 0 END) AS missed_visits_count')
            ->selectRaw('COUNT(DISTINCT visits.id) AS total_visits_count')
            ->rightJoin('visits', 'clients.id', '=', 'visits.client_id')
            ->whereNull('visits.deleted_at')
            ->leftJoin('users', 'visits.user_id', '=', 'users.id')
            ->groupBy('clients.id','clients.name_en');

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

    private static function getMedicalReps(): array
    {
        if(self::$medicalReps)
            return self::$medicalReps;

        self::$medicalReps = User::getMine()->pluck('name','id')->toArray();
        return self::$medicalReps;
    }



    // public static function resolveRecordRouteBinding(int | string $key): ?Model
    // {
    //     return app(static::getModel())
    //         ->resolveRouteBindingQuery(static::getEloquentQuery(), $key, static::getRecordRouteKeyName())
    //         ->first();
    // }

    public static function getRecordRouteKeyName(): string|null {
        return 'clients.id';
    }

    private static function getBricks(): array
    {
        if(self::$bricks)
            return self::$bricks;

        self::$bricks = Brick::getMine()->pluck('name','id')->toArray();
        return self::$bricks;
    }

    private static function gradeAVG(): array
    {
        if(self::$avgGrade)
            return self::$avgGrade;
        $query = Client::query()
            ->select('grade')
            ->selectRaw('SUM(CASE WHEN visits.status = "visited" THEN 1 ELSE 0 END) AS done_visits')
            ->selectRaw('SUM(CASE WHEN visits.status = "cancelled" THEN 1 ELSE 0 END) AS missed_visits')
            ->leftJoin('visits', 'clients.id', '=', 'visits.client_id')
            ->groupBy('grade')
            ->get();

        $output = [
            'A' => 'A - 0 %',
            'B' => 'B - 0 %',
            'C' => 'C - 0 %',
            'N' => 'N - 0 %',
            'PH' => 'PH - 0 %',
        ];

        foreach ($query as $result) {
            $grade = $result->grade;
            $done_visits = $result->done_visits;
            $missed_visits = $result->missed_visits;

            $total = $done_visits + $missed_visits;

            if ($total) {
                $percentage = round($done_visits / $total, 4) * 100;
            } else {
                $percentage = 0;
            }

            $output[$grade] = $grade . ' - ' . $percentage . ' %';
        }
        self::$avgGrade = $output;
        return $output;
    }
}
