<?php

namespace App\Filament\Resources;

use App\Exports\SamplesReportExport;
use App\Filament\Resources\SamplesReportResource\Pages;
use App\Models\Product;
use App\Models\User;
use App\Models\Visit;
use App\Traits\ResourceHasPermission;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SamplesReportResource extends Resource
{
    use ResourceHasPermission;

    protected static ?string $model = Visit::class;

    protected static ?string $permissionName = 'samples-report';

    protected static ?string $label = 'Samples report';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Samples Report';

    protected static ?string $slug = 'samples-report';

    protected static $medicalReps;

    protected static $products;

    public static function getEloquentQuery(): Builder
    {
        return Visit::query()
            ->select(
                'product_visits.id as id',
                'visits.id as visit_id',
                'visits.user_id as user_id',
                'visits.visit_date as visit_date',
                'users.name as medical_rep',
                'clients.name_en as client_name',
                'products.id as product_id',
                'products.name as product_name',
                'product_visits.count as samples_count',
            )
            ->join('product_visits', 'product_visits.visit_id', '=', 'visits.id')
            ->leftJoin('users', 'users.id', '=', 'visits.user_id')
            ->leftJoin('clients', 'clients.id', '=', 'visits.client_id')
            ->leftJoin('products', 'products.id', '=', 'product_visits.product_id')
            ->where('visits.status', 'visited')
            ->where('product_visits.count', '>', 0)
            ->orderBy('visits.visit_date', 'DESC');
    }

    public static function getRecordRouteKeyName(): ?string
    {
        return 'id';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('visit_date')
                    ->date('Y-m-d')
                    ->label('Visit Date'),
                TextColumn::make('medical_rep')
                    ->label('Delivered By'),
                TextColumn::make('client_name')
                    ->label('Client'),
                TextColumn::make('product_name')
                    ->label('Product'),
                TextColumn::make('samples_count')
                    ->label('Samples')
                    ->summarize(Sum::make()->label('Total')),
            ])
            ->groups([
                Group::make('user_id')
                    ->label('Medical Rep')
                    ->getTitleFromRecordUsing(fn ($record): string => $record->medical_rep),
            ])
            ->defaultGroup('user_id')
            ->filters([
                Tables\Filters\Filter::make('dates_range')
                    ->form([
                        DatePicker::make('from_date')
                            ->default(today()->startOfMonth()),
                        DatePicker::make('to_date')
                            ->default(today()->endOfMonth()),
                    ])->columns(2)
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visits.visit_date', '>=', $date)
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('visits.visit_date', '<=', $date));
                    }),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Medical Rep')
                    ->multiple()
                    ->options(self::getMedicalReps())
                    ->query(function (Builder $query, array $data): Builder {
                        if (count($data['values'])) {
                            return $query->whereIn('visits.user_id', $data['values']);
                        } else {
                            return $query;
                        }
                    }),
                Tables\Filters\SelectFilter::make('product_id')
                    ->label('Product')
                    ->multiple()
                    ->options(self::getProducts())
                    ->query(function (Builder $query, array $data): Builder {
                        if (count($data['values'])) {
                            return $query->whereIn('product_visits.product_id', $data['values']);
                        } else {
                            return $query;
                        }
                    }),
            ])
            ->headerActions([
                Action::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Export Samples Report')
                    ->modalDescription('This will export the samples distribution data based on current filters to an Excel file.')
                    ->modalSubmitActionLabel('Yes, Export')
                    ->action(function ($livewire) {
                        try {
                            $query = $livewire->getFilteredTableQuery();

                            // Get current date range from filters
                            $dateRange = $livewire->tableFilters['dates_range'] ?? [];
                            $fromDate = $dateRange['from_date'] ?? null;
                            $toDate = $dateRange['to_date'] ?? null;

                            $dateRangeString = '';
                            if ($fromDate && $toDate) {
                                $dateRangeString = $fromDate.'_to_'.$toDate;
                            } elseif ($fromDate) {
                                $dateRangeString = 'from_'.$fromDate;
                            } elseif ($toDate) {
                                $dateRangeString = 'until_'.$toDate;
                            }

                            $export = new SamplesReportExport($query, $dateRangeString);

                            return $export->download($export->getFilename());
                        } catch (\Exception $e) {
                            // Handle export errors gracefully
                            return redirect()->back()->with('error', 'Export failed: '.$e->getMessage());
                        }
                    }),
            ])
            ->actions([
            ])
            ->bulkActions([
            ]);
    }

    private static function getMedicalReps(): array
    {
        if (self::$medicalReps) {
            return self::$medicalReps;
        }

        self::$medicalReps = User::allMine()->pluck('name', 'id')->toArray();

        return self::$medicalReps;
    }

    private static function getProducts(): array
    {
        if (self::$products) {
            return self::$products;
        }

        self::$products = Product::pluck('name', 'id')->toArray();

        return self::$products;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSamplesReports::route('/'),
        ];
    }
}
