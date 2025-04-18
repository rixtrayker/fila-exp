<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitReportResource\Pages;
use App\Models\Client;
use App\Models\ClientType;
use App\Models\User;
use App\Models\Visit;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Traits\ResouerceHasPermission;

class VisitReportResource extends Resource
{
    use ResouerceHasPermission;
    protected static ?string $model = Client::class;
    protected static ?string $label = 'Visit report';

    protected static ?string $navigationLabel = 'Visit report';
    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $slug = 'visits-report';
    protected static $avgGrade;
    protected static $clientTypes;
    protected static $medicalReps;
    protected static $districtManager;


    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('medical_rep')
                    ->label('Medical Rep'),
                TextColumn::make('client_name')
                    ->label('Client'),
                TextColumn::make('brick_name')
                    ->label('Brick'),
                TextColumn::make('visit_date')
                    ->date('Y-m-d')
                    ->label('Visit Date'),
                TextColumn::make('status')
                    ->label('Status')
                    ->weight(FontWeight::Bold)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                            'cancelled' => 'danger',
                            'planned' => 'warning',
                            'pending' => 'gray',
                            'visited' => 'success',
                            default => null,
                        })
                    ->icon(fn (string $state): string => match ($state) {
                        'cancelled' => 'heroicon-m-x-circle',
                        'planned' => 'heroicon-s-clock',
                        'pending' => 'heroicon-m-list-bullet',
                        'visited' => 'heroicon-m-check-circle',
                        default => null,
                    })
                    ->iconPosition(IconPosition::After),
                TextColumn::make('product_list')
                    ->label('List of products'),
            ])
            ->filters([
                Tables\Filters\Filter::make('id')
                    ->form([
                        Select::make('user_id')
                            ->label('Medical Rep')
                            ->multiple()
                            ->options(self::getMedicalReps()),
                        Select::make('second_user_id')
                            ->label('Manager')
                            ->multiple()
                            ->options(self::getDistrictManagers()),
                    ])->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['user_id'],
                                fn (Builder $query, $userIds): Builder => $query->whereIn('user_id', $userIds)
                            )
                            ->when(
                                $data['second_user_id'],
                                fn (Builder $query, $secondIds): Builder => $query->orWhereIn('second_user_id', $secondIds)
                            );
                    }),
                Tables\Filters\Filter::make('visit_date')
                    ->form([
                            Forms\Components\DatePicker::make('from_date')
                                ->default(today()->subDays(7)),
                            Forms\Components\DatePicker::make('to_date')
                                ->default(today()),
                        ])->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '>=', $date)
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '<=', $date));
                    }),
                Tables\Filters\Filter::make('grade_and_status')
                    ->form([
                        Select::make('grade')
                            ->label('Grade')
                            // ->multiple()
                            ->options(['A'=>'A','B'=>'B','C'=>'C','N'=>'N','PH'=>'PH']),
                        Select::make('status')
                            ->label('Status')
                            // ->multiple()
                            ->options([
                                'cancelled' => 'Cancelled',
                                'planned' => 'Planned',
                                'pending' => 'Pending',
                                'visited' => 'Visited'
                            ]),
                        ])->columns(2)
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['grade'],
                                    fn (Builder $query, $data): Builder => $query->where('clients.grade', $data)
                                )
                                ->when(
                                    $data['status'],
                                    fn (Builder $query, $data): Builder => $query->where('status', $data)
                                );}),
                Tables\Filters\Filter::make('client_types')
                    ->form([
                        Select::make('client_type_id')
                            ->label('Client Type')
                            ->options(self::getClientTypes())
                        ])->columns(2)
                        ->query(function (Builder $query, array $data): Builder {
                            return $query
                                ->when(
                                    $data['client_type_id'],
                                    fn (Builder $query, $data): Builder => $query->where('clients.client_type_id', $data)
                                );}),
            ])
            ->paginated([10, 25, 50, 100, 1000, 'all'])
            ->actions([
                Tables\Actions\ViewAction::make(),
                // Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    private static function getMedicalReps(): array
    {
        if(self::$medicalReps)
            return self::$medicalReps;

        self::$medicalReps = User::allMine()->pluck('name','id')->toArray();
        return self::$medicalReps;
    }
    private static function getDistrictManagers(): array
    {
        if(self::$districtManager)
            return self::$districtManager;

        self::$districtManager = User::allWithRole('district-manager')->pluck('name','id')->toArray();
        return self::$districtManager;
    }

    private static function getClientTypes() : array {
        if(self::$clientTypes){
            return self::$clientTypes;
        }
        else{
            self::$clientTypes = ClientType::pluck('name','id')->toArray();
            return self::$clientTypes;
        }
    }

    public static function getEloquentQuery(): Builder
    {
        DB::statement("SET SESSION sql_mode=''");
        return Visit::select(
            'visits.id as id',
            'visits.client_id as client_id',
            'visits.user_id as user_id',
            'clients.grade as grade',
            'clients.name_en as client_name',
            'clients.brick_id as brick_id',
            'bricks.name as brick_name',
            'visits.status as status',
            'users.name as medical_rep',
            'visits.visit_date as visit_date',
            // 'visits.second_user_id as second_user_id',
            DB::raw('GROUP_CONCAT( products.name SEPARATOR ", ") AS product_list')
        )
            ->selectRaw('SUM(CASE WHEN visits.status = "visited" THEN 1 ELSE 0 END) AS done_visits_count')
            ->selectRaw('MAX(CASE WHEN visits.status IN ("pending", "planned") THEN 1 ELSE 0 END) AS pending_visits_count')
            ->selectRaw('SUM(CASE WHEN visits.status = "cancelled" THEN 1 ELSE 0 END) AS missed_visits_count')
            ->selectRaw('COUNT(DISTINCT visits.id) AS total_visits_count')
            ->leftJoin('clients', 'clients.id', '=', 'visits.client_id')
            ->leftJoin('users', 'users.id', '=', 'visits.user_id')
            ->leftJoin('product_visits', 'product_visits.visit_id', '=', 'visits.id')
            ->leftJoin('products', 'product_visits.product_id', '=', 'products.id')
            ->leftJoin('bricks', 'clients.brick_id', '=', 'bricks.id')
            ->groupBy('visits.id')
            ->orderBy('visits.id', 'DESC');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisitReports::route('/'),
            'view' => Pages\ViewVist::route('/{record}'),
        ];
    }
    public static function canCreate(): bool
    {
        return false;
    }

    public static function getRecordRouteKeyName(): string|null {
        return 'visits.id';
    }
}
