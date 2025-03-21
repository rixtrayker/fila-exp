<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Resources\PlanResource\RelationManagers;
use App\Helpers\DateHelper;
use App\Models\Client;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use Filament\Tables\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LucasGiovanny\FilamentMultiselectTwoSides\Forms\Components\Fields\MultiselectTwoSides as newMultiSelect;
use App\Livewire\MultiSelect2Sides as MultiselectTwoSides;
use App\Models\Plan;
use App\Models\Visit;
use App\Traits\ResouerceHasPermission;
use Closure;
use Str;
use Filament\Forms\Components\TimePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class PlanResource extends Resource
{
    use ResouerceHasPermission;

    protected static ?string $model = Plan::class;
    protected static ?string $navigationLabel = 'Weekly plans';
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $slug = 'plans';
    protected static ?int $navigationSort = 2;
    protected static $clients = [];

    public static function form(Form $form): Form
    {
        for($i=0; $i<7; $i++){
            self::dates()[$i] = Carbon::createFromTimeString(self::dates()[$i].' 00:00:00')->format('D M-d');
        }

        return $form
            ->schema([
                self::makeForm()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('approved_by')
                    ->label('Approved By'),
                TextColumn::make('start_at')
                ->dateTime('d-M-Y')
                ->sortable()
                ->searchable(),
                TextColumn::make('end_date')
                ->dateTime('d-M-Y')
                ->sortable()
                ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
            Tables\Actions\ViewAction::make(),
            Tables\Actions\EditAction::make(),
            Tables\Actions\Action::make('approve')
                ->label('Approve')
                ->color('success')
                ->icon('heroicon-o-check')
                ->visible(fn($record) => $record->canApprove())
                ->action(fn($record)=> $record->approvePlan()),
            Tables\Actions\Action::make('reject')
                ->label('Reject & Delete')
                ->color('danger')
                ->icon('heroicon-m-x-mark')
                ->visible(fn($record) => $record->canDecline())
                ->requiresConfirmation()
                ->action(fn($record) => $record->rejectPlan()),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getClients(): array
    {
        if(self::$clients)
            return self::$clients;

        self::$clients = Client::inMyAreas()->pluck('name_en', 'id')->toArray();
        return self::$clients;
    }
    public static function visitDates(): array{
        return [];
    }

    public static function getPlanDayState($record, $day)
    {
        if(!$record){
            return [];
        }

        $clients = Visit::where('plan_id',$record->id)->where('visit_date',self::dates()[$day])->pluck('client_id')->toArray();
        return $clients;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
            'view' => Pages\ViewPlan::route('/{record}'),
        ];
    }

    public static function isCreatePage(): bool
    {
        return true;
    }

    private static function dates():array {
        return DateHelper::calculateVisitDates();
    }

    private static function makeForm()
    {
        $tabs = [];
        foreach(self::dates() as $day => $date){
            $tabs[] =self::makeTab($day);
        }

        return Tabs::make('Weekly Plan')
                ->tabs($tabs)->columnSpanFull();
    }
    private static function makeTab($key)
    {
        $days = ['sat','sun','mon','tues','wednes','thurs','fri'];

        $day = $days[$key];
        $tabName = Carbon::createFromTimeString(self::dates()[$key].' 00:00:00')->format('D M-d');

        return Tabs\Tab::make($tabName)
            ->schema([
                Select::make($day.'_am_shift')
                    ->label('AM shift')
                    ->searchable()
                    ->default(fn($record)=> request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans') ? $record->shiftClient($days[$key])->am_shift : null)
                    ->getSearchResultsUsing(fn (string $search) => Client::inMyAreas()->where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                    ->options(self::getClients())
                    ->preload(),
                TimePicker::make($day.'_time_am')
                    ->default(fn($record)=> request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans') ? $record->shiftClient($days[$key])->am_time : null)
                    ->label('AM time')
                    ->native(false)
                    ->withoutSeconds(),
                Select::make($day.'_pm_shift')
                    ->label('PM shift')
                    ->searchable()
                    ->default(fn($record)=> request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans') ? $record->shiftClient($days[$key])->pm_shift : null)
                    ->getSearchResultsUsing(fn (string $search) => Client::inMyAreas()->where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                    ->options(self::getClients())
                    ->preload(),
                TimePicker::make($day.'_time_pm')
                    ->default(fn($record)=> request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans') ? $record->shiftClient($days[$key])->pm_time : null)
                    ->label('PM time')
                    ->native(false)
                    ->withoutSeconds(),
                Select::make($day.'_clients')
                    ->label('Clients')
                    ->multiple()
                    ->options(self::getClients())
                    // ->relationship($days[$key].'Clients', 'name_en')
                    ->preload(),
            ]);
    }

    public static function canCreate(): bool
    {
        $visitDates = DateHelper::calculateVisitDates();

        return ! Plan::query()
            ->where('user_id', auth()->id())
            ->where('start_at', $visitDates[0])
            ->exists();
    }

    public static function getEloquentQuery(): Builder
    {
        return static::getModel()::query()->latest();
    }
    public static function canEdit(Model $record): bool
    {
        $lastPlanId = $record->user?->plans()->latest()->first()?->id;
        return $record->id == $lastPlanId && !$record->approved;
    }
}
