<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DailyVisitResource\Pages;
use App\Filament\Resources\DailyVisitResource\RelationManagers;
use App\Helpers\DateHelper;
use App\Models\Visit;
use App\Traits\ResourceHasPermission;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;

class DailyVisitResource extends Resource
{
    use ResourceHasPermission;
    protected static ?string $model = Visit::class;
    protected static ?string $navigationLabel = 'Daily visits';
    protected static ?string $label = 'Daily visits';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    protected static ?string $slug = 'daily-visits';
    protected static ?int $navigationSort = 1;
    protected static ?string $navigationGroup = 'Visits';
    protected static string $view = 'vendor.filament.pages.create-visit';

    public static function form(Form $form): Form
    {
        return VisitResource::form($form);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')->sortable(),
                TextColumn::make('user.name')
                    ->label('M.Rep')
                    ->hidden(auth()->user()->hasRole('medical-rep'))
                    ->sortable(),
                TextColumn::make('client.name_en')
                    ->label('Client')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.brick.name')
                    ->label('Brick')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('secondRep.name')
                    ->label('Double name'),
                TextColumn::make('visit_date')
                    ->label('Visit Date')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('comment')
                    ->limit(100)
                    ->wrap(),
            ])
            ->filters([
                Tables\Filters\Filter::make('days')
                    ->default()
                    ->label('Day of Plan')
                    ->form([
                        Forms\Components\Toggle::make('next_plan')
                            ->hint('Starting on '.DateHelper::getFirstOfWeek(true)->format('Y-m-d'))
                            ->label('Next Plan')
                            ->default(false),
                        Forms\Components\Select::make('day')
                            ->label('Day of Plan')
                            ->default(DateHelper::dayOfWeek())
                            ->options([
                                0 => 'Saturday',
                                1 => 'Sunday',
                                2 => 'Monday',
                                3 => 'Tuesday',
                                4 => 'Wednesday',
                                5 => 'Thursday',
                                6 => 'Friday',
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $nextPlan = $data['next_plan'];
                        $startOfWeek = DateHelper::getFirstOfWeek($nextPlan);
                        $visitDate = $startOfWeek->addDays($data['day']%7);

                        return $query
                            ->where('visit_date', $visitDate);

                    }),
                Tables\Filters\SelectFilter::make('brick')
                    ->label('Brick')
                    ->searchable()
                    ->relationship('brick','name'),
            ])
            ->actions([
                Tables\Actions\Action::make('swap')
                    ->label('Swap')
                    ->color('gray')
                    ->icon('heroicon-m-arrows-right-left')
                    ->form(function ($record){
                        return [
                            DatePicker::make('visit_date')
                                ->label('Visit Date')
                                ->default($record->visit_date)
                                ->minDate(Carbon::today())
                                ->maxDate(fn($record) => $record->plan->lastDay())
                                ->required(),
                        ];
                    })
                    ->action(fn($record, $data) => $record->changeDate($data['visit_date'])),
                Tables\Actions\EditAction::make()
                    ->visible(fn($record) => self::isTodayVisit($record) ),
                ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('swap')
                    ->label('Swap')
                    ->color('gray')
                    ->icon('heroicon-m-arrows-right-left')
                    ->form(function ($records){
                        return [
                            DatePicker::make('visit_date')
                                ->label('Visit Date')
                                ->default($records->first()->visit_date)
                                ->minDate(fn($records) => self::minDate($records->first()->plan->start_at))
                                ->maxDate($records->first()->plan->lastDay())
                                ->required(),
                        ];
                    })
                    ->action(fn (Collection $records, $data) => $records->each(fn (Model $record) => $record->changeDate($data['visit_date']))),
                // Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['client.brick','user'])
            ->scopes([
                'pending',
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyVisits::route('/'),
            // 'create' => Pages\CreateDailyVisit::route('/create'),
            'edit' => Pages\EditDailyVisit::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
    public static function canEdit(Model $visit): bool
    {
        return self::isTodayVisit($visit);
    }

    public static function minDate($planStart): Carbon
    {
        if(Carbon::today()->isBefore($planStart))
            return $planStart;
        return DateHelper::today();
    }

    private static function isTodayVisit(Visit $visit): bool {
        $today = DateHelper::today();
        return $visit->visit_date == $today;
    }
}
