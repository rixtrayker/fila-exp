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
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Forms\Components\Tabs;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use LucasGiovanny\FilamentMultiselectTwoSides\Forms\Components\Fields\MultiselectTwoSides as newMultiSelect;
use App\Http\Livewire\MultiSelect2Sides as MultiselectTwoSides;
use App\Models\Plan;
use App\Models\Visit;
use Closure;
use Str;
use Filament\Forms\Components\TimePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Log;

class PlanResource extends Resource
{
    // protected static bool $shouldRegisterNavigation = false;

    protected static ?string $model = Plan::class;
    protected static ?string $navigationLabel = 'Weekly Plans';
    protected static ?string $navigationIcon = 'heroicon-o-calendar';
    protected static ?string $slug = 'plans';
    protected static ?int $navigationSort = 2;
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
            Tables\Actions\Action::make('show Achieved')
                // ->
                ->color('primary')
                ->icon('heroicon-s-clipboard-check')
                ->action(fn()=>Log::channel('debugging')->info(request()->fingerprint['name']))
                ->form([
                    Select::make('user_id')
                        ->label('Agent (medical rep)')
                        ->relationship('user','name')
                        ->default(fn($record)=>$record->user_id)
                        ->disabled(),
                    static::makeForm(),
                ])->modalWidth('5xl'),
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
        return Client::orderBy('name_en')->get()->pluck('name', 'id')->toArray();
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
            // 'edit' => Pages\EditPlan::route('/{record}/edit'),
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
                Select::make($day.'_am')
                    ->label('AM shift')
                    ->searchable()
                    ->disabled(fn()=>request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans'))
                    ->default(fn($record)=> request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans') ? $record->shiftClient($days[$key])->am_shift : null)
                    ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                    ->options(Client::pluck('name_en', 'id'))
                    ->preload(),
                TimePicker::make($day.'_time_am')
                    ->disabled(fn()=>request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans'))
                    ->default(fn($record)=> request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans') ? $record->shiftClient($days[$key])->am_time : null)
                    ->label('AM time')
                    ->withoutSeconds(),
                Select::make($day.'_pm')
                    ->label('PM shift')
                    ->searchable()
                    ->disabled(fn()=>request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans'))
                    ->default(fn($record)=> request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans') ? $record->shiftClient($days[$key])->pm_shift : null)
                    ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                    ->options(Client::pluck('name_en', 'id'))
                    ->preload(),
                TimePicker::make($day.'_time_pm')
                    ->disabled(fn()=>request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans'))
                    ->default(fn($record)=> request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans') ? $record->shiftClient($days[$key])->pm_time : null)
                    ->label('PM time')
                    ->withoutSeconds(),
                MultiselectTwoSides::make('clients_'.$day)
                    ->disableLabel()
                    ->defaultSelectOptions(
                        function($record) use ($key){
                            if(request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans')){
                                $options = Visit::withoutGlobalScope('all')
                                    ->where('user_id',$record->user_id)
                                    ->where('status','visited')
                                    ->where('visit_date',self::dates()[$key])
                                    ->pluck('client_id')->toArray();

                                return $options;
                            }

                            return self::getPlanDayState($record, $key);
                        })
                    ->options(
                        function($record) use ($key){
                            if(request()->fingerprint && Str::contains(request()->fingerprint['name'],'list-plans')){
                                $visits = Visit::withoutGlobalScope('all')
                                    ->where('user_id',$record->user_id)
                                    ->whereIn('status',['visited','planned'])
                                    ->where('visit_date',self::dates()[$key])
                                    ->get();

                                $clientIds = $visits->pluck('client_id')->toArray();
                                $clients = Client::orderBy('name_en')->whereIn('id',$clientIds)->get()->pluck('name', 'id')->toArray();
                                $options = [];
                                foreach($clients as $key => $client){
                                    $firstClientVisit = $visits->where('client_id',$key)->first();
                                    $options[$key] =  $firstClientVisit && $firstClientVisit->plan_id !== null ? $client : $client.' ( not planned ).';
                                }
                                return $options;
                            }
                            return self::getClients($record, $key);
                        }),
            ]);
    }
}
