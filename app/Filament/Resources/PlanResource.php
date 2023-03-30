<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PlanResource\Pages;
use App\Filament\Resources\PlanResource\RelationManagers;
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
// use LucasGiovanny\FilamentMultiselectTwoSides\Forms\Components\Fields\MultiselectTwoSides;
use App\Http\Livewire\MultiSelect2Sides as MultiselectTwoSides;
use App\Models\Plan;
use Closure;
use Filament\Tables\Columns\TextColumn;

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
        return $form
            ->schema([
                Tabs::make('Weekly Plan')
                ->tabs([
                    Tabs\Tab::make('Saturday')
                        ->schema([
                            Select::make('sat_am')
                            ->label('AM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload(),
                            Select::make('sat_pm')
                            ->label('PM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload(),
                            MultiselectTwoSides::make('clients_saturday')
                                ->options(self::getClients(1)),
                        ]),
                    Tabs\Tab::make('Sunday')
                        ->schema([
                            Select::make('sun_am')
                            ->label('AM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload(),
                            Select::make('sun_pm')
                            ->label('PM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload(),
                            MultiselectTwoSides::make('clients_sunday')
                                ->options(self::getClients(2)),
                        ]),
                    Tabs\Tab::make('Monday')
                        ->schema([
                            Select::make('mon_am')
                            ->label('AM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload()
                            ->required(function(Closure $get){
                                $all = $get('clients_monday');
                                foreach($all as $one){
                                    if(explode('_',$one)[1] == 3)
                                        return true;
                                }
                                return false;
                            }),
                            Select::make('mon_pm')
                            ->label('PM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload()
                            ->required(function(Closure $get){
                                $all = $get('clients_monday');
                                foreach($all as $one){
                                    if(explode('_',$one)[1] == 3)
                                        return true;
                                }
                                return false;
                            }),
                            Select::make('sat_am')
                            ->label('AM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload(),
                            Select::make('sat_pm')
                            ->label('PM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload(),
                            MultiselectTwoSides::make('clients_monday')
                                ->options(self::getClients(3)),
                            ]),
                    Tabs\Tab::make('Tuesday')
                        ->schema([
                            Select::make('tues_am')
                            ->label('AM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload(),
                            Select::make('tues_pm')
                            ->label('PM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload(),
                            MultiselectTwoSides::make('clients_tuesday')
                                ->options(self::getClients(4)),
                            ]),
                    Tabs\Tab::make('Wednesday')
                        ->schema([
                            Select::make('wednes_am')
                            ->label('AM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload(),
                            Select::make('wednes_pm')
                            ->label('PM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload(),
                            MultiselectTwoSides::make('clients_wednesday')
                                ->options(self::getClients(5)),
                            ]),
                    Tabs\Tab::make('Thursday')
                        ->schema([
                            Select::make('thurs_am')
                            ->label('AM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload(),
                            Select::make('thurs_pm')
                            ->label('PM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload(),
                            MultiselectTwoSides::make('clients_thursday')
                                ->options(self::getClients(6)),
                        ]),
                    Tabs\Tab::make('Friday')
                        ->schema([
                            Select::make('fri_am')
                            ->label('AM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload(),
                            Select::make('fri_pm')
                            ->label('PM shift')
                            ->searchable()
                            ->placeholder('You can search both arabic and english name')
                            ->getSearchResultsUsing(fn (string $search) => Client::where('name_en', 'like', "%{$search}%")->orWhere('name_ar', 'like', "%{$search}%")->limit(50)->pluck('name_en', 'id'))
                            ->options(Client::pluck('name_en', 'id'))
                            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                            ->preload(),
                            MultiselectTwoSides::make('clients_friday')
                                ->options(self::getClients(7)),
                        ]),
                ])->columnSpanFull()
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
                Tables\Actions\EditAction::make(),
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

    public static function getClients($num): array
    {
        $result = [];
        $clients = Client::orderBy('name_en')->get()->pluck('name', 'id');
        foreach( $clients as $key => $value ) {
            $result[$key.'_'.$num] = $value;
        }
        return $result;
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPlans::route('/'),
            'create' => Pages\CreatePlan::route('/create'),
            'edit' => Pages\EditPlan::route('/{record}/edit'),
        ];
    }
}
