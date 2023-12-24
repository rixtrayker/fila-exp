<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExpensesReportResource\Pages;
use App\Models\Expenses;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class ExpensesReportResource extends Resource
{
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
                'daily_allowance',
                DB::raw('SUM(transportation) as transportation'),
                DB::raw('SUM(lodging) as lodging'),
                DB::raw('SUM(mileage) as mileage'),
                DB::raw('SUM(meal) as meal'),
                DB::raw('SUM(telephone_postage) as telephone_postage'),
                DB::raw('SUM(medical_expenses) as medical_expenses'),
                DB::raw('SUM(others) as others'),
                DB::raw('SUM(total) as total'),
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
                TextColumn::make('transportation')
                    ->label('Transportation'),
                TextColumn::make('lodging')
                    ->label('Lodging'),
                TextColumn::make('mileage')
                    ->label('Mileage'),
                TextColumn::make('meal')
                    ->label('Meal'),
                TextColumn::make('telephone_postage')
                    ->label('Postage/Telephone/Fax'),
                TextColumn::make('daily_allowance')
                    ->label('Daily Allowance'),
                TextColumn::make('medical_expenses')
                    ->label('Medical Expenses'),
                TextColumn::make('others')
                    ->label('Others'),
                TextColumn::make('total')
                    ->label('Total'),
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
                            return $query->whereIn('vacation_requests.user_id', $data['values']);
                        }
                        else
                            return $query;
                        }),
                    ]);
    }

    private static function getMedicalReps(): array
    {
        if(self::$medicalReps)
            return self::$medicalReps;

        self::$medicalReps = User::getMine()->pluck('name','id')->toArray();
        return self::$medicalReps;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExpensesReports::route('/'),
        ];
    }
}
