<?php

namespace App\Filament\Resources\VisitResource\Tables;

use App\Models\ClientType;
use App\Models\User;
use App\Models\VisitType;
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
        return $table
            ->columns(self::getColumns())
            ->filters(self::getFilters())
            ->actions(self::getActions())
            ->bulkActions(self::getBulkActions());
    }

    /**
     * Get the table columns configuration.
     */
    private static function getColumns(): array
    {
        return [
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

            // Visit-related columns
            TextColumn::make('visit_date')
                ->dateTime('d-M-Y')
                ->sortable()
                ->searchable(),
            TextColumn::make('visitType.name')
                ->label('Visit Type'),
            TextColumn::make('comment')
                ->limit(100)
                ->wrap(),
        ];
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
            self::getVisitTypeFilter(),
            self::getClientTypeFilter(),
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
                    ->options(User::getMine()->pluck('name', 'id')),
                Select::make('second_user_id')
                    ->label('Manager')
                    ->multiple()
                    ->options(User::role('district-manager')->getMine()->pluck('name', 'id')),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['user_id'],
                        fn (Builder $query, $userIds): Builder => $query->whereIn('user_id', $userIds)
                    )
                    ->when(
                        $data['second_user_id'],
                        fn (Builder $query, $secondIds): Builder => $query->orWhereIn('second_user_id', $secondIds)
                    );
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
     * Get the visit type filter configuration.
     */
    private static function getVisitTypeFilter(): SelectFilter
    {
        return SelectFilter::make('visit_type_id')
            ->label('Visit Type')
            ->multiple()
            ->options(VisitType::pluck('name', 'id'))
            ->query(function (Builder $query, array $data): Builder {
                if ($data['values']) {
                    return $query->whereHas('client', function ($q) use ($data) {
                        $q->whereIn('visit_type_id', $data['values']);
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
     * Get the table actions configuration.
     */
    private static function getActions(): array
    {
        return [
            Tables\Actions\ViewAction::make(),
            Tables\Actions\DeleteAction::make()
                ->hidden(auth()->user()->hasRole('medical-rep')),
            Tables\Actions\RestoreAction::make()
                ->hidden(fn($record) => $record->deleted_at == null)
        ];
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
