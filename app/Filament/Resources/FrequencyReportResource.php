<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FrequencyReportResource\Pages;
use App\Models\Brick;
use App\Models\Client;
use App\Models\ClientType;
use App\Models\Reports\FrequencyReportData;
use App\Traits\ResourceHasPermission;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Enums\FiltersLayout;

class FrequencyReportResource extends Resource
{
    use ResourceHasPermission;
    protected static ?string $model = Client::class;
    protected static ?string $label = 'Frequency report';
    protected static ?string $navigationLabel = 'Frequency report';
    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $slug = 'frequency-report';

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
                // TextColumn::make('brick.area.name')
                    // ->label('Area'),
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
                TextColumn::make('achievement_percentage')
                    ->label('Achievement %'),
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
                Tables\Filters\SelectFilter::make('brick_id')
                    ->label('Brick')
                    ->options(Brick::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->multiple(),
                Tables\Filters\SelectFilter::make('grade')
                    ->options(['A' => 'A', 'B' => 'B', 'C' => 'C', 'N' => 'N', 'PH' => 'PH']),
                Tables\Filters\SelectFilter::make('client_type_id')
                    ->label('Client Type')
                    ->options(ClientType::all()->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->multiple(),
            ])
            ->filtersLayout(FiltersLayout::AboveContent)
            ->paginated([10, 25, 50, 100, 1000, 'all'])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getEloquentQuery(): Builder
    {
        $dateRange = request()->get('tableFilters')['date_range'] ?? [];
        $fromDate = $dateRange['from_date'] ?? today()->startOfMonth();
        $toDate = $dateRange['to_date'] ?? today()->endOfMonth();

        $filters = [
            'brick_id' => request()->get('tableFilters')['brick_id'] ?? null,
            'grade' => request()->get('tableFilters')['grade'] ?? null,
            'client_type_id' => request()->get('tableFilters')['client_type_id'] ?? null,
        ];

        return FrequencyReportData::getAggregatedQuery($fromDate, $toDate, $filters);
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
}
