<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpensesReportResource\Pages;
use App\Models\Expenses;
use App\Models\User;
use App\Exports\ExpensesReportExport;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use App\Traits\ResourceHasPermission;
class ExpensesReportResource extends Resource
{
    use ResourceHasPermission;
    protected static ?string $model = Expenses::class;

    protected static ?string $navigationGroup = 'Reports';
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Expenses Report';

    protected static $medicalReps;


    public static function getEloquentQuery(): Builder
    {
        DB::statement("SET SESSION sql_mode=''");

        return Expenses::query()
            ->select(
                'users.id as id',
                'users.name as medical_rep',
                DB::raw('COUNT(*) as total_expenses'),
            )
            ->leftJoin('users', 'users.id','=','expenses.user_id')
            ->groupBy(
                'users.id',
            );
    }

    public static function getRecordRouteKeyName(): string|null {
        return 'users.id';
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('medical_rep')
                    ->label('Medical Rep'),
                TextColumn::make('total_expenses')
                    ->label('Total Expenses'),
            ])
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
                            fn (Builder $query, $date): Builder => $query->whereDate('date', '>=', $date)
                        )
                        ->when(
                            $data['to_date'],
                            fn (Builder $query, $date): Builder => $query->whereDate('date', '<=', $date));
                }),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Medical Rep')
                    ->multiple()
                    ->options(self::getMedicalReps())
                    ->query(function (Builder $query, array $data): Builder {
                        if(count($data['values'])){
                            return $query->whereIn('expenses.user_id', $data['values']);
                        }
                        else
                            return $query;
                        }),
                    ])
            ->headerActions([
                Action::make('export')
                    ->label('Export to Excel')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Export Expenses Report')
                    ->modalDescription('This will export all expenses data based on current filters to an Excel file. The export includes all expense categories with proper formatting and styling.')
                    ->modalSubmitActionLabel('Yes, Export')
                    ->action(function () {
                        try {
                            $query = static::getEloquentQuery();

                            // Get current date range from filters
                            $tableFilters = request()->get('tableFilters', []);
                            $dateRange = $tableFilters['dates_range'] ?? [];
                            $fromDate = $dateRange['from_date'] ?? null;
                            $toDate = $dateRange['to_date'] ?? null;

                            $dateRangeString = '';
                            if ($fromDate && $toDate) {
                                $dateRangeString = $fromDate . '_to_' . $toDate;
                            } elseif ($fromDate) {
                                $dateRangeString = 'from_' . $fromDate;
                            } elseif ($toDate) {
                                $dateRangeString = 'until_' . $toDate;
                            }

                            $export = new ExpensesReportExport($query, $dateRangeString);
                            return $export->download($export->getFilename());
                        } catch (\Exception $e) {
                            // Handle export errors gracefully
                            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
                        }
                    })
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('export_selected')
                    ->label('Export Selected')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Export Selected Records')
                    ->modalDescription('This will export only the selected expenses records to an Excel file.')
                    ->modalSubmitActionLabel('Yes, Export Selected')
                    ->action(function ($records) {
                        try {
                            $export = new ExpensesReportExport($records->toQuery(), 'selected_records');
                            return $export->download($export->getFilename());
                        } catch (\Exception $e) {
                            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
                        }
                    })
            ]);
    }

    private static function getMedicalReps(): array
    {
        if(self::$medicalReps)
            return self::$medicalReps;

        self::$medicalReps = User::allMine()->pluck('name','id')->toArray();
        return self::$medicalReps;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpensesReports::route('/'),
        ];
    }
}
