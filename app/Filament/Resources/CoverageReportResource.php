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

class CoverageReportResource extends Resource
{
    use ResourceHasPermission;
    protected static ?string $model = Visit::class;
    protected static ?string $label = 'Coverage report';

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

    public static function table(Table $table): Table
    {
        return $table
            ->defaultPaginationPageOption(10)
            ->columns([
                TextColumn::make('client.name_en')
                    ->searchable()
                    ->label('Client Name'),
                TextColumn::make('visit_date')
                    ->date()
                    ->label('Visit Date'),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'visited' => 'success',
                        'pending' => 'warning',
                        'planned' => 'info',
                        'missed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('user.name')
                    ->label('Medical Rep'),
            ])
            // ->filters([
            //     Tables\Filters\SelectFilter::make('status')
            //         ->options([
            //             'visited' => 'Visited',
            //             'pending' => 'Pending',
            //             'planned' => 'Planned',
            //             'missed' => 'Missed',
            //         ])
            //         ->multiple()
            //         ->query(function (Builder $query, array $data): Builder {
            //             $this->dispatch('updateVisitsList');
            //             return $query;
            //         }),
            //     Tables\Filters\SelectFilter::make('user_id')
            //         ->label('Medical Rep')
            //         ->relationship('user', 'name')
            //         ->multiple(),
            //     Tables\Filters\Filter::make('visit_date')
            //         ->form([
            //             Forms\Components\DatePicker::make('from_date')
            //                 ->default(today()->subDays(7)),
            //             Forms\Components\DatePicker::make('to_date')
            //                 ->default(today()),
            //         ])
            //         ->query(function (Builder $query, array $data): Builder {
            //             return $query
            //                 ->when(
            //                     $data['from_date'],
            //                     fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '>=', $date)
            //                 )
            //                 ->when(
            //                     $data['to_date'],
            //                     fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '<=', $date)
            //                 );
            //         })
            // ])
            ->paginated([10, 25, 50, 100, 1000, 'all'])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        return Visit::query()
            ->with(['client', 'user'])
            ->whereNull('deleted_at');
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
