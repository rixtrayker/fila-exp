<?php

namespace App\Filament\Resources\VisitResource\Tables;

use App\Models\Bundle;
use App\Models\ClientType;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VisitTable
{
    public static function table(Table $table): Table
    {
        $isBreakdown = request()->get('breakdown') === 'true';

        $table = $table
            ->columns(self::getColumns())
            ->filters(self::getFilters())
            ->actions(self::getActions())
            ->bulkActions(self::getBulkActions())
            ->paginated([50, 100, 250, 500, 1000])
            ->defaultPaginationPageOption(50);

        return $table;
    }

    /**
     * Get the table columns configuration.
     */
    private static function getColumns(): array
    {
        $columns = [
            // User-related columns
            TextColumn::make('user.name')
                ->label('M.Rep')
                ->hidden(auth()->user()->hasRole('medical-rep'))
                ->sortable(),
            TextColumn::make('secondRep.name')
                ->label('Double name'),

            // Client-related columns
            TextColumn::make('client.name_en')
                ->label('Client')
                ->searchable()
                ->sortable(),
            TextColumn::make('clientType.name')
                ->label('Client Type'),
            TextColumn::make('client.grade')
                ->label('Client Grade'),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'visited' => 'success',
                    'pending' => 'warning',
                    'cancelled' => 'danger',
                    'planned' => 'info',
                    'missed' => 'gray',
                    default => 'gray',
                })
                ->icon(fn (string $state): string => match ($state) {
                    'visited' => 'heroicon-m-check-circle',
                    'pending' => 'heroicon-m-clock',
                    'cancelled' => 'heroicon-m-x-circle',
                    'planned' => 'heroicon-m-calendar',
                    'missed' => 'heroicon-m-exclamation-circle',
                    default => 'heroicon-m-question-mark-circle',
                }),

            // label is_planned green for the visit has plan id and grey (secondary) for the visit has no plan id
            TextColumn::make('is_planned')
                ->label('Planned')
                ->badge()
                ->formatStateUsing(fn ($state) => $state ? 'Planned' : 'Random')
                ->color(fn ($record) => $record->is_planned ? 'success' : 'gray'),
            // Visit-related columns
            TextColumn::make('visit_date')
                ->dateTime('d-M-Y')
                ->sortable()
                ->searchable(),
            TextColumn::make('created_at')
                ->label('Created At')
                ->date('d-m-Y h:i A')
                // ->description('date format is 31-12-2025 10:00 AM')
                ->tooltip(fn($record) => $record->created_at->format('d-M-Y'))
                // ->copyable()
                // ->copyMessage('Copied!')
                // ->copyMessageDuration(500)
                ->sortable()
                ->searchable(),
            TextColumn::make('feedback')
                ->label('Feedback')
                ->searchable()
                ->sortable(),
            TextColumn::make('comment')
                ->limit(100)
                ->wrap(),
            TextColumn::make('bundles.name')
                ->label('Bundles')
                ->badge()
                ->separator(','),
        ];

        return $columns;
    }

    /**
     * Get the table filters configuration.
     */
    private static function getFilters(): array
    {
        return [
            self::getUserFilter(),
            self::getDateFilter(),
            self::getGradeFilter(),
            self::getClientTypeFilter(),
            self::getBundlesFilter(),
            self::getPlannedFilter(),
            self::getStatusFilter(),
            TrashedFilter::make(),
        ];
    }

    /**
     * Get the user filter configuration.
     */
    private static function getUserFilter(): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make('id')
            ->form([
                Select::make('user_id')
                    ->label('Medical Rep')
                    ->multiple()
                    ->options(User::allMine()->pluck('name', 'id')),
                Select::make('second_user_id')
                    ->label('Manager')
                    ->multiple()
                    ->options(User::allWithRole('district-manager')->getMine()->pluck('name', 'id')),
            ])
            ->query(function (Builder $query, array $data): Builder {
                $userIds = $data['user_id'] ?? [];

                if (!empty($userIds)) {
                    $query->where(function (Builder $nested) use ($userIds) {
                        $nested->whereIn('user_id', $userIds);
                        $nested->orWhereIn('second_user_id', $userIds);
                    });
                }

                return $query;
            });
    }

    /**
     * Get the date filter configuration.
     */
    private static function getDateFilter(): Tables\Filters\Filter
    {
        return Tables\Filters\Filter::make('visit_date')
            ->form([
                DatePicker::make('from_date'),
                DatePicker::make('to_date'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['from_date'],
                        fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '>=', $date)
                    )
                    ->when(
                        $data['to_date'],
                        fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '<=', $date)
                    );
            });
    }

    /**
     * Get the grade filter configuration.
     */
    private static function getGradeFilter(): SelectFilter
    {
        return SelectFilter::make('grade')
            ->label('Grade')
            ->multiple()
            ->options(['A' => 'A', 'B' => 'B', 'C' => 'C', 'N' => 'N', 'PH' => 'PH'])
            ->query(function (Builder $query, array $data): Builder {
                if ($data['values']) {
                    return $query->whereHas('client', function ($q) use ($data) {
                        $q->whereIn('grade', $data['values']);
                    });
                }
                return $query;
            });
    }

    /**
     * Get the client type filter configuration.
     */
    private static function getClientTypeFilter(): SelectFilter
    {
        return SelectFilter::make('client_type_id')
            ->label('Client Type')
            ->multiple()
            ->options(ClientType::pluck('name', 'id'))
            ->query(function (Builder $query, array $data): Builder {
                if ($data['values']) {
                    return $query->whereHas('client', function ($q) use ($data) {
                        $q->whereIn('client_type_id', $data['values']);
                    });
                }
                return $query;
            });
    }

    /**
     * Get the bundles filter configuration.
     */
    private static function getBundlesFilter(): SelectFilter
    {
        return SelectFilter::make('bundles')
            ->label('Bundles')
            ->multiple()
            ->options(Bundle::active()->pluck('name', 'id'))
            ->query(function (Builder $query, array $data): Builder {
                if ($data['values']) {
                    return $query->whereHas('bundles', function ($q) use ($data) {
                        $q->whereIn('bundles.id', $data['values']);
                    });
                }
                return $query;
            });
    }

    /**
     * Get the table actions configuration.
     */
    private static function getActions(): array
    {

        return [
            Tables\Actions\ViewAction::make(),
            Tables\Actions\DeleteAction::make()
                ->hidden(fn() => auth()->user()->hasRole('medical-rep')),
            Tables\Actions\RestoreAction::make()
                ->hidden(fn($record) => $record->deleted_at == null)
        ];
    }

    private static function getPlannedFilter(): SelectFilter
    {
        return SelectFilter::make('is_planned')
            ->label('Planned')
            ->multiple()
            ->options([true => 'Planned', false => 'Random'])
            ->query(function (Builder $query, array $data): Builder {
                if (empty($data['values'])) {
                    return $query;
                }

                $planned = in_array(true, $data['values']);
                $random = in_array(false, $data['values']);

                return match (true) {
                    $planned && $random => $query,
                    $planned => $query->whereNotNull('plan_id'),
                    $random => $query->whereNull('plan_id'),
                    default => $query,
                };
            });
    }
    // get status filter
    private static function getStatusFilter(): SelectFilter
    {
        return SelectFilter::make('status')
            ->label('Status')
            ->options(['visited' => 'Visited', 'pending' => 'Pending', 'cancelled' => 'Cancelled', 'planned' => 'Planned', 'missed' => 'Missed'])
            ->query(function (Builder $query, array $data): Builder {
                if (empty($data['values'])) {
                    return $query;
                }
                return $query->whereIn('status', $data['values']);
            });
    }
    /**
     * Get the table bulk actions configuration.
     */
    private static function getBulkActions(): array
    {
        return [
            Tables\Actions\DeleteBulkAction::make(),
            Tables\Actions\RestoreBulkAction::make(),
            Tables\Actions\ForceDeleteBulkAction::make(),
        ];
    }
}
