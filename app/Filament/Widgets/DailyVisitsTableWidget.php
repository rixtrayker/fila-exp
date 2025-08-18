<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use App\Helpers\DateHelper;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class DailyVisitsTableWidget extends BaseWidget
{
    protected static ?string $heading = 'Daily Visits ( Pending Plan visits )';
    protected static ?int $sort = 2;

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    protected function getTableQuery(): Builder
    {
        $today = DateHelper::today();

        return Visit::query()
            ->with(['client.brick', 'client.clientType', 'user', 'callType'])
            ->whereDate('visit_date', $today)
            ->whereIn('status', ['pending', 'visited'])
            ->whereNotNull('plan_id');
            // ->orderBy('status', 'asc');
            // ->orderBy('client.name_en', 'asc');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('client.name_en')
                ->label('Client')
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('client.clientType.name')
                ->label('Type')
                ->badge()
                ->color(fn (string $state): string => match (true) {
                    str_contains(strtolower($state), 'hospital') => 'success',
                    str_contains(strtolower($state), 'clinic') => 'info',
                    str_contains(strtolower($state), 'pharmacy') => 'warning',
                    default => 'gray',
                }),

            Tables\Columns\TextColumn::make('client.brick.name')
                ->label('Brick')
                ->sortable(),

            Tables\Columns\TextColumn::make('user.name')
                ->label('Rep')
                ->sortable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('callType.name')
                ->label('Call Type')
                ->badge()
                ->toggleable(),

            Tables\Columns\TextColumn::make('visit_date')
                ->label('Visit Date')
                ->date()
                ->sortable(),

            Tables\Columns\TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'pending' => 'warning',
                    'visited' => 'success',
                    'cancelled' => 'danger',
                    default => 'gray',
                }),
        ];
    }

    protected function getTableFilters(): array
    {
        return [];
    }

    protected function getTableActions(): array
    {
        return [];
    }

    protected function getTableBulkActions(): array
    {
        return [];
    }

    public function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'client.name_en';
    }

    protected function getDefaultTableSortDirection(): ?string
    {
        return 'asc';
    }

    protected function getTableGroups(): array
    {
        return [
            'status',
            // 'user.name',
        ];
    }

    protected function getDefaultTableGroup(): ?string
    {
        return 'status';
    }
}
