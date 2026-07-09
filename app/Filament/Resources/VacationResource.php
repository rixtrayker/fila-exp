<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VacationResource\Pages;
use App\Filament\Resources\VacationResource\RelationManagers;
use App\Models\User;
use App\Models\Vacation;
use App\Models\VacationDuration;
use App\Models\VacationRequest;
use App\Models\VacationType;
use App\Services\VacationCalculator;
use App\Traits\ResourceHasPermission;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
use Carbon\Carbon;
use Closure;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VacationResource extends Resource
{
    use ResourceHasPermission;
    protected static ?string $model = VacationRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 2;
    protected static ?string $navigationGroup = 'Requests';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('vacation_type_id')
                    ->label('Vacation Type')
                    ->options(VacationType::all()->pluck('name', 'id'))
                    ->preload()
                    ->required(),
                Section::make('Durations')
                    ->hiddenLabel()
                    ->schema([
                        TableRepeater::make('vacationDurations')
                            ->relationship('vacationDurations')
                            ->reactive()
                            ->hiddenLabel()
                            ->headers(['Start date','End date' , 'From shift', 'To Shift'])
                            ->emptyLabel('There is no vacation duration added.')
                            ->columnWidths([
                                'start' => '240px',
                                'end' => '240px',
                                'start_shift' => '140px',
                                'end_shift' => '140px',
                                'row_actions' => '20px',
                            ])
                        ->schema([
                            DatePicker::make('start')
                                ->hiddenLabel()
                                ->closeOnDateSelection()
                                ->minDate(today())
                                ->reactive()
                                ->default(today())
                                ->required(),

                                DatePicker::make('end')
                                ->minDate(fn($get) => Carbon::parse($get('start') ? $get('start') : today()))
                                ->reactive()
                                ->hiddenLabel()
                                ->closeOnDateSelection()
                                ->required(),
                            Select::make('start_shift')
                                ->hiddenLabel()
                                ->default('AM')
                                ->options(['AM'=>'AM','PM'=>'PM'])
                                ->reactive()
                                ->required(),
                            Select::make('end_shift')
                                ->hiddenLabel()
                                ->default('PM')
                                ->options(['AM'=>'AM','PM'=>'PM'])
                                ->rules([
                                    function ($get) {
                                        return function (string $attribute, $value, Closure $fail) use ($get) {
                                            $start = Carbon::parse($get('start') ? $get('start') : today());
                                            $end = Carbon::parse($get('end') ? $get('end') : today());

                                            $start_shift = $get('start_shift');
                                            $end_shift = $get('end_shift');

                                            if ($end_shift == 'AM' && $start_shift == 'PM' && !($end->isAfter($start)))
                                                $fail("Wrong shifts due to dates input ".$start->format('Y-m-d'));
                                        };
                                    },
                                ])
                                ->required(),
                        ])->disableItemMovement()
                        ->defaultItems(1),
                    ])->compact(),
                Placeholder::make('balance_summary')
                    ->label('Vacation Balance')
                    ->content(function ($get, $record) {
                        $calculator = app(VacationCalculator::class);

                        $requestedDays = 0;
                        foreach ($get('vacationDurations') ?? [] as $duration) {
                            if (blank($duration['start'] ?? null) || blank($duration['end'] ?? null))
                                continue;

                            $requestedDays += $calculator->calculateTotalDuration(
                                $duration['start'],
                                $duration['end'],
                                $duration['start_shift'] ?? 'AM',
                                $duration['end_shift'] ?? 'PM'
                            );
                        }

                        $user = $record?->repUser ?? auth()->user();

                        if (!$user)
                            return 'Requesting '.$requestedDays.' day(s)';

                        $remainingDays = self::getRemainingBalance($user, $record?->id);

                        return 'Requesting '.$requestedDays.' day(s), '.$user->name.' has '.$remainingDays.' remaining day(s) this year';
                    }),
            ]);
    }

    /**
     * Remaining annual balance for a user: entitlement minus approved
     * vacation days spent in the current year.
     */
    public static function getRemainingBalance(User $user, ?int $excludeRequestId = null): float
    {
        $spentDays = VacationDuration::query()
            ->join('vacation_requests as vr', 'vr.id', '=', 'vacation_durations.vacation_request_id')
            ->where('vr.user_id', $user->id)
            ->where('vr.approved', '>', 0)
            ->when($excludeRequestId, fn($query) => $query->where('vr.id', '!=', $excludeRequestId))
            ->whereYear('vacation_durations.start', now()->year)
            ->sum('vacation_durations.duration');

        return (float) ($user->annual_vacation_days ?? 21) - (float) $spentDays;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->withSum('vacationDurations as requested_days', 'duration')
                ->addSelect([
                    'approved_spent_days' => VacationDuration::query()
                        ->join('vacation_requests as vr', 'vr.id', '=', 'vacation_durations.vacation_request_id')
                        ->whereColumn('vr.user_id', 'vacation_requests.user_id')
                        ->where('vr.approved', '>', 0)
                        ->whereYear('vacation_durations.start', now()->year)
                        ->selectRaw('COALESCE(SUM(vacation_durations.duration), 0)'),
                ]))
            ->columns([
                TextColumn::make('repUser.name')
                    ->label('Medical Rep')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('star')
                    ->label('Start date') // todo:
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('end_at')
                    ->label('End date')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('requested_days')
                    ->label('Requested Days')
                    ->state(fn($record) => $record->requested_days ?? $record->duration)
                    ->sortable(),
                TextColumn::make('remaining_balance')
                    ->label('Remaining Balance')
                    ->state(fn($record) => $record->repUser
                        ? $record->repUser->annual_vacation_days - $record->approved_spent_days
                        : null),
                IconColumn::make('approved')
                    ->colors(function($record){
                        if($record->approved > 0)
                            return ['success' => $record->approved];
                        if($record->approved < 0)
                            return ['danger' => $record->approved];

                        return ['secondary' => $record->approved];
                    })
                    ->options(function($record){
                        if($record->approved > 0)
                                return ['heroicon-o-check-circle' => $record->approved];
                        if($record->approved < 0)
                            return ['heroicon-o-x-circle' =>  $record->approved];
                        return ['heroicon-o-clock' => $record->approved];
                    }),
                TextColumn::make('approved_by')
                    ->label('Approved By'),
                TextColumn::make('approved_at')
                    ->label('Approved at')
                    ->dateTime('d-M-Y')
                    ->sortable()
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->visible(fn($record) => $record->canApprove())
                    ->action(fn($record) => $record->approve()),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->color('danger')
                    ->icon('heroicon-m-x-mark')
                    ->visible(fn($record) => $record->canDecline())
                    ->action(fn($record) => $record->reject()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVacations::route('/'),
            'create' => Pages\CreateVacation::route('/create'),
            'edit' => Pages\EditVacation::route('/{record}/edit'),
        ];
    }
}
