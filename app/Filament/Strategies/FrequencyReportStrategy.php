<?php

namespace App\Filament\Strategies;

use App\Models\Visit;
use App\Models\User;
use App\Models\Client;
use App\Models\Brick;
use App\Models\ClientType;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;

class FrequencyReportStrategy implements VisitBreakdownStrategyInterface
{
    public function configureQuery(Builder $query, array $filters): Builder
    {
        return $query
            ->with(['client', 'user'])
            ->when($filters['from_date'] ?? null, fn($q) => $q->whereDate('visit_date', '>=', $filters['from_date']))
            ->when($filters['to_date'] ?? null, fn($q) => $q->whereDate('visit_date', '<=', $filters['to_date']))
            ->when($filters['client_id'] ?? null, fn($q) => $q->where('client_id', $filters['client_id']))
            ->when($filters['user_id'] ?? null, fn($q) => $q->where('user_id', $filters['user_id']))
            ->when($filters['status'] ?? null, fn($q) => $q->where('status', $filters['status']))
            ->when($filters['brick_id'] ?? null, function($q) use ($filters) {
                $q->whereHas('client', function($clientQuery) use ($filters) {
                    $clientQuery->whereIn('brick_id', (array) $filters['brick_id']);
                });
            })
            ->when($filters['grade'] ?? null, function($q) use ($filters) {
                $q->whereHas('client', function($clientQuery) use ($filters) {
                    $clientQuery->whereIn('grade', (array) $filters['grade']);
                });
            })
            ->when($filters['client_type_id'] ?? null, function($q) use ($filters) {
                $q->whereHas('client', function($clientQuery) use ($filters) {
                    $clientQuery->whereIn('client_type_id', (array) $filters['client_type_id']);
                });
            })
            ->orderBy('visit_date', 'desc');
    }

    public function getTableColumns(): array
    {
        return [
            TextColumn::make('client.name_en')
                ->label('Client')
                ->sortable()
                ->searchable()
                ->weight(FontWeight::SemiBold)
                ->icon('heroicon-m-building-office-2')
                ->iconColor('primary'),

            TextColumn::make('client.grade')
                ->label('Grade')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'A' => 'success',
                    'B' => 'info',
                    'C' => 'warning',
                    'N' => 'gray',
                    'PH' => 'danger',
                    default => 'gray',
                }),

            TextColumn::make('client.brick.name')
                ->label('Brick')
                ->sortable()
                ->searchable()
                ->toggleable(),

            TextColumn::make('user.name')
                ->label('Medical Rep')
                ->sortable()
                ->searchable()
                ->weight(FontWeight::SemiBold)
                ->icon('heroicon-m-user')
                ->iconColor('info'),

            TextColumn::make('visit_date')
                ->label('Visit Date')
                ->date('M j, Y')
                ->sortable()
                ->description(fn($record) => $record->visit_date->format('l'))
                ->icon('heroicon-m-calendar-days')
                ->iconColor('gray'),

            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    'visited' => 'success',
                    'pending' => 'warning',
                    'missed' => 'danger',
                    default => 'gray',
                })
                ->icon(fn (string $state): string => match ($state) {
                    'visited' => 'heroicon-m-check-circle',
                    'pending' => 'heroicon-m-clock',
                    'missed' => 'heroicon-m-x-circle',
                    default => 'heroicon-m-question-mark-circle',
                }),

            TextColumn::make('comment')
                ->label('Notes')
                ->limit(50)
                ->tooltip(function ($record) { 
                    return $record->comment ?: 'No notes available';
                })
                ->placeholder('No notes')
                ->color('gray'),

            TextColumn::make('created_at')
                ->label('Created')
                ->since()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }

    public function getTableFilters(): array
    {
        return [
            Filter::make('date_range')
                ->form([
                    DatePicker::make('from_date')
                        ->label('From Date')
                        ->default(now()->startOfMonth()),
                    DatePicker::make('to_date')
                        ->label('To Date')
                        ->default(now()->endOfDay()),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when(
                            $data['from_date'],
                            fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '>=', $date),
                        )
                        ->when(
                            $data['to_date'],
                            fn (Builder $query, $date): Builder => $query->whereDate('visit_date', '<=', $date),
                        );
                }),

            SelectFilter::make('client')
                ->relationship('client', 'name_en')
                ->searchable()
                ->preload(),

            SelectFilter::make('user')
                ->relationship('user', 'name')
                ->searchable()
                ->preload()
                ->label('Medical Rep'),

            SelectFilter::make('brick_id')
                ->label('Brick')
                ->options(Brick::all()->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->multiple(),

            SelectFilter::make('grade')
                ->label('Client Grade')
                ->options(['A' => 'A', 'B' => 'B', 'C' => 'C', 'N' => 'N', 'PH' => 'PH'])
                ->multiple(),

            SelectFilter::make('client_type_id')
                ->label('Client Type')
                ->options(ClientType::all()->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->multiple(),

            SelectFilter::make('status')
                ->options([
                    'visited' => 'Visited',
                    'pending' => 'Pending',
                    'missed' => 'Missed',
                ])
                ->multiple(),
        ];
    }

    public function getTableActions(): array
    {
        return [
            Action::make('view')
                ->label('View Details')
                ->icon('heroicon-m-eye')
                ->color('primary')
                ->url(fn ($record) => route('filament.admin.resources.visits.view', $record))
                ->openUrlInNewTab(),

            Action::make('view_client')
                ->label('View Client')
                ->icon('heroicon-m-building-office-2')
                ->color('info')
                ->url(fn ($record) => route('filament.admin.resources.clients.view', $record->client))
                ->openUrlInNewTab(),
        ];
    }

    public function getStats(array $filters): array
    {
        $query = Visit::query()
            ->when($filters['from_date'] ?? null, fn($q) => $q->whereDate('visit_date', '>=', $filters['from_date']))
            ->when($filters['to_date'] ?? null, fn($q) => $q->whereDate('visit_date', '<=', $filters['to_date']))
            ->when($filters['client_id'] ?? null, fn($q) => $q->where('client_id', $filters['client_id']))
            ->when($filters['user_id'] ?? null, fn($q) => $q->where('user_id', $filters['user_id']))
            ->when($filters['brick_id'] ?? null, function($q) use ($filters) {
                $q->whereHas('client', function($clientQuery) use ($filters) {
                    $clientQuery->whereIn('brick_id', (array) $filters['brick_id']);
                });
            })
            ->when($filters['grade'] ?? null, function($q) use ($filters) {
                $q->whereHas('client', function($clientQuery) use ($filters) {
                    $clientQuery->whereIn('grade', (array) $filters['grade']);
                });
            })
            ->when($filters['client_type_id'] ?? null, function($q) use ($filters) {
                $q->whereHas('client', function($clientQuery) use ($filters) {
                    $clientQuery->whereIn('client_type_id', (array) $filters['client_type_id']);
                });
            });

        return [
            'total_visits' => $query->count(),
            'visited' => $query->clone()->where('status', 'visited')->count(),
            'pending' => $query->clone()->where('status', 'pending')->count(),
            'missed' => $query->clone()->where('status', 'missed')->count(),
            'unique_clients' => $query->clone()->distinct('client_id')->count('client_id'),
        ];
    }

    public function getPageTitle(array $filters): string
    {
        if ($filters['client_id'] ?? null) {
            $client = Client::find($filters['client_id']);
            return $client ? "Visit Breakdown - {$client->name_en}" : 'Client Visit Analysis';
        }
        
        return 'Client Visit Analysis';
    }

    public function getTableHeading(): string
    {
        return 'Client Visit Records';
    }

    public function getClientBreakdown(array $filters): array
    {
        // For frequency report breakdown, show visits for the specific client only
        $query = Visit::query()
            ->with(['client', 'user'])
            ->when($filters['from_date'] ?? null, fn($q) => $q->whereDate('visit_date', '>=', $filters['from_date']))
            ->when($filters['to_date'] ?? null, fn($q) => $q->whereDate('visit_date', '<=', $filters['to_date']))
            ->when($filters['user_id'] ?? null, fn($q) => $q->where('user_id', $filters['user_id']))
            ->when($filters['brick_id'] ?? null, function($q) use ($filters) {
                $q->whereHas('client', function($clientQuery) use ($filters) {
                    $clientQuery->whereIn('brick_id', (array) $filters['brick_id']);
                });
            })
            ->when($filters['grade'] ?? null, function($q) use ($filters) {
                $q->whereHas('client', function($clientQuery) use ($filters) {
                    $clientQuery->whereIn('grade', (array) $filters['grade']);
                });
            })
            ->when($filters['client_type_id'] ?? null, function($q) use ($filters) {
                $q->whereHas('client', function($clientQuery) use ($filters) {
                    $clientQuery->whereIn('client_type_id', (array) $filters['client_type_id']);
                });
            });

        // IMPORTANT: Filter by specific client_id for frequency report breakdown
        if ($filters['client_id'] ?? null) {
            $query->where('client_id', $filters['client_id']);
        }

        return $query
            ->selectRaw('user_id, status, COUNT(*) as count')
            ->groupBy('user_id', 'status')
            ->get()
            ->groupBy('user_id')
            ->map(function ($visits) {
                $user = $visits->first()->user;
                $statusCounts = $visits->pluck('count', 'status')->toArray();
                
                return [
                    'user' => $user,
                    'user_name' => $user->name,
                    'total' => array_sum($statusCounts),
                    'visited' => $statusCounts['visited'] ?? 0,
                    'pending' => $statusCounts['pending'] ?? 0,
                    'missed' => $statusCounts['missed'] ?? 0,
                    'frequency_rate' => array_sum($statusCounts) > 0 
                        ? round(($statusCounts['visited'] ?? 0) / array_sum($statusCounts) * 100, 2)
                        : 0
                ];
            })
            ->sortByDesc('total')
            ->take(10)
            ->toArray();
    }
}