<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FrequencyReportResource\Pages;
use App\Models\Brick;
use App\Models\Client;
use App\Models\User;
use App\Traits\ResourceHasPermission;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Models\ClientType;

class FrequencyReportResource extends Resource
{
    use ResourceHasPermission;
    protected static ?string $model = Client::class;
    protected static ?string $label = 'Frequency report';

    protected static ?string $navigationLabel = 'Frequency report';
    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $slug = 'frequency-report';

    protected static $avgGrade;
    protected static $bricks;
    protected static $medicalReps;


    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                TextColumn::make('name_en')
                    ->searchable()
                    ->label('Name'),
                TextColumn::make('client_type_name')
                    ->label('Client Type'),
                TextColumn::make('grade')
                    ->label('Grade'),
                TextColumn::make('brick.name')
                    ->label('Brick'),
                TextColumn::make('brick.area.name')
                    ->label('Area'),
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
                TextColumn::make('achivement_percentage')
                    ->label('Achivement %'),
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
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query->whereHas('visits', function ($q) use ($data) {
                            $q->whereBetween('visit_date', [$data['from_date'], $data['to_date']]);
                        });
                    }),
                Tables\Filters\SelectFilter::make('brick_id')
                    ->label('Brick')
                    ->relationship('brick', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('grade')
                    ->options(fn()=>self::gradeAVG()),
                Tables\Filters\SelectFilter::make('client_type_id')
                    ->label('Client Type')
                    ->relationship('clientType', 'name')
                    ->searchable()
                    ->preload()
                    ->multiple(),
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
        $dateRange = request()->get('tableFilters')['date_range'] ?? [];
        $fromDate = $dateRange['from_date'] ?? today()->startOfMonth();
        $toDate = $dateRange['to_date'] ?? today()->endOfMonth();

        $clientType = request()->get('tableFilters')['client_type_id'] ?? null;
        $brick = request()->get('tableFilters')['brick_id'] ?? null;
        $grade = request()->get('tableFilters')['grade'] ?? null;

        DB::statement("SET SESSION sql_mode=''");

        return Client::select(
            'clients.id as id',
            'client_types.name as client_type_name',
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
            ->leftJoin('client_types', 'clients.client_type_id', '=', 'client_types.id')
            ->groupBy('clients.id','clients.name_en')
            ->when($clientType, function ($query, $clientType) {
                return $query->whereIn('clients.client_type_id', $clientType);
            })
            ->when($fromDate, function ($query, $fromDate) {
                return $query->where('visits.visit_date', '>=', $fromDate);
            })
            ->when($toDate, function ($query, $toDate) {
                return $query->where('visits.visit_date', '<=', $toDate);
            })
            ->when($brick, function ($query, $brick) {
                return $query->whereIn('clients.brick_id', $brick);
            })
            ->when($grade, function ($query, $grade) {
                return $query->where('clients.grade', $grade);
            });

    }

    public static function getRecordRouteKeyName(): string|null {
        return 'clients.id';
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

        self::$medicalReps = User::allMine()->pluck('name','id')->toArray();
        return self::$medicalReps;
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
        $dateRange = request()->get('tableFilters')['date_range'] ?? [];
        $fromDate = $dateRange['from_date'] ?? today()->startOfMonth();
        $toDate = $dateRange['to_date'] ?? today()->endOfMonth();

        if(self::$avgGrade)
            return self::$avgGrade;
        $query = Client::query()
            ->select('grade')
            ->selectRaw('SUM(CASE WHEN visits.status = "visited" THEN 1 ELSE 0 END) AS done_visits')
            ->selectRaw('SUM(CASE WHEN visits.status = "cancelled" THEN 1 ELSE 0 END) AS missed_visits')
            ->leftJoin('visits', 'clients.id', '=', 'visits.client_id')
            ->whereBetween('visits.visit_date', [$fromDate, $toDate])
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
