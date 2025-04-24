<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CoverageReportResource\Pages;
use App\Models\Visit;
use App\Traits\ResourceHasPermission;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use App\Models\User;
use App\Models\Area;
use App\Models\ClientType;

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

    public $from;
    public $to;
    public $user_id = [];

    public function __construct()
    {
        $this->from = today()->subDays(7);
        $this->to = today();
    }

    public static function getEloquentQuery(): Builder
    {
        // Get filter values from request
        $fromDate = request()->get('tableFilters.visit_date.from_date') ?? today()->startOfMonth();
        $toDate = request()->get('tableFilters.visit_date.to_date') ?? today()->endOfMonth();

        // Only include medical-rep or district manager roles using spatie permission
        return User::role(['medical-rep', 'district-manager'])
            ->with(['visits' => function ($query) use ($fromDate, $toDate) {
                $query->whereBetween('visit_date', [$fromDate, $toDate]);
            }])
            ->with(['activities' => function ($query) use ($fromDate, $toDate) {
                $query->whereDate('created_at', '>=', $fromDate)->whereDate('created_at', '<=', $toDate);
            }])
            ->with(['officeWorks' => function ($query) use ($fromDate, $toDate) {
                $query->whereDate('created_at', '>=', $fromDate)->whereDate('created_at', '<=', $toDate);
            }]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('id')
                    ->label('ID'),
                TextColumn::make('name')
                    ->searchable()
                    ->label('Medical Rep'),
                // area
                TextColumn::make('area_name')
                    ->searchable()
                    ->label('Area'),
                // working days
                TextColumn::make('working_days')
                    ->label('Working Days'),
                // daily visit target
                TextColumn::make('daily_visit_target')
                    ->label('Daily Visit Target'),
                // office work
                TextColumn::make('office_work_count')
                    ->label('Office Work'),
                // activities
                TextColumn::make('activities_count')
                    ->label('Activities'),
                // actual working days
                TextColumn::make('actual_working_days')
                    ->label('Actual Working Days'),
                // monthly visits target
                TextColumn::make('monthly_visit_target')
                    ->label('Monthly Visits Target'),
                // SOPs
                TextColumn::make('sops')
                    ->label('SOPs %')
                    ->formatStateUsing(function ($state) {
                        return "{$state}%";
                    }),
                // Daily report
                TextColumn::make('actual_visits')
                    ->label('Actual Visits'),
                // Call rate
                TextColumn::make('call_rate')
                    ->label('Call Rate'),
                // total visits
                TextColumn::make('total_visits')
                    ->label('Total Visits'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'visited' => 'Visited',
                        'pending' => 'Pending',
                        'planned' => 'Planned',
                        'missed' => 'Missed',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('visits', function ($q) use ($data) {
                            $q->whereIn('status', $data['values']);
                        });
                    })
                    ->multiple(),
                Tables\Filters\Filter::make('visit_date')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->default(today()->subDays(7)),
                        Forms\Components\DatePicker::make('to_date')
                            ->default(today()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // This filter doesn't directly apply to users but will be used in the getEloquentQuery
                        return $query;
                    }),
                Tables\Filters\SelectFilter::make('area')
                    ->label('Area')
                    ->options(Area::all()->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('areas', function ($q) use ($data) {
                            $q->whereIn('area_id', $data['values']);
                        });
                    })
                    ->multiple(),
                // class of clients
                Tables\Filters\SelectFilter::make('grade')
                    ->label('Client Class')
                    ->options(['A' => 'A', 'B' => 'B', 'C' => 'C', 'N' => 'N', 'PH' => 'PH'])
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('visits', function ($q) use ($data) {
                            $q->whereHas('client', function ($q) use ($data) {
                                $q->whereIn('grade', $data['values']);
                            });
                        });
                    })
                    ->multiple(),
                // client type
                Tables\Filters\SelectFilter::make('client_type_id')
                    ->label('Client Type')
                    ->options(ClientType::all()->pluck('name', 'id'))
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['values'])) {
                            return $query;
                        }

                        return $query->whereHas('visits', function ($q) use ($data) {
                            $q->whereHas('client', function ($q) use ($data) {
                                $q->whereIn('client_type_id', $data['values']);
                            });
                        });
                    })
                    ->multiple(),
            ])
            ->paginated([10, 25, 50, 100, 1000, 'all'])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
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

    #[On('updateVisitsList')]
    public function updateVisitsList($eventData)
    {
        $this->from = $eventData['from'];
        $this->to = $eventData['to'];
        $this->user_id = $eventData['user_id'];
    }
}