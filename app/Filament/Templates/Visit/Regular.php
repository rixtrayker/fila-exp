<?php

namespace App\Filament\Templates\Visit;

use App\Models\CallType;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use App\Models\VisitType;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Str;

final class Regular
{
    public static $doubleCallTypeId;
    public static function title()
    {
        return 'Regular';
    }

    public static function schema()
    {
        static::$doubleCallTypeId = CallType::where('name','Double')->value('id');

        return [
            Fieldset::make('Regular Visit Fields')
            ->schema([
            Select::make('user_id')
                    ->label('Medical Rep')
                    ->searchable()
                    ->relationship('user','name')
                    ->placeholder('Search name')
                    ->getSearchResultsUsing(fn (string $search) => User::getMine()->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->options(User::getMine()->pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->disabled(Str::contains(request()->path(),'daily-visits'))
                    ->hidden(auth()->user()->hasRole('medical-rep'))
                    ->preload(),
                Select::make('second_user_id')
                    ->label('Visit Accompany')
                    ->relationship('secondRep','name')
                    ->searchable()
                    ->rules([
                        function ($get) {
                            return function (string $attribute, $value, Closure $fail) use ($get) {
                                if ($value && $get('call_type_id') != static::$doubleCallTypeId) {
                                    $fail("The Visit Accompany must be empty unless the call type is Double.");
                                }
                            };
                        },
                    ])
                    ->required(fn($get)=> $get('call_type_id') == static::$doubleCallTypeId)
                    ->placeholder('Search name')
                    ->getSearchResultsUsing(fn (string $search) => User::role('district-manager')->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->options(User::role('district-manager')->pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->preload(),
                Select::make('client_id')
                    ->label('Client')
                    ->searchable()
                    ->relationship('client','name')
                    ->placeholder('Search by name or phone or speciality')
                    ->disabled(Str::contains(request()->path(),'daily-visits'))
                    ->getSearchResultsUsing(function(string $search){
                        return Client::inMyAreas()->where('name_en', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhereHas('speciality', function ($q) use ($search) {
                                $q->where('name','like', "%{$search}%");
                            })->limit(50)->pluck('name_en', 'id');
                    })
                    ->options(Client::inMyAreas()->pluck('name_en', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                    ->preload()
                    ->required(!Str::contains(request()->path(),'daily-visits')),
                Select::make('call_type_id')
                    ->label('Call Type')
                    ->options(CallType::all()->pluck('name', 'id'))
                    ->preload()
                    ->required(),
                DatePicker::make('next_visit')
                    ->label('Next call time')
                    ->closeOnDateSelection()
                    ->minDate(today()->addDay()),
                DatePicker::make('visit_date')
                    ->label('Visit Date')
                    ->default(today()),
                Section::make('products')
                    ->hiddenLabel()
                    ->schema([
                    TableRepeater::make('products')
                        ->createItemButtonLabel('Add product')
                        ->relationship(function(){
                            if(
                                Str::contains(request()->path(),'create')
                                ||
                                Str::contains(request()->path(),'edit')
                            ) return 'nullRelation';
                            return 'products';
                        })
                        ->hiddenLabel()
                        // ->headers(['Product', 'Sample Count'])
                        ->emptyLabel('There is no product added.')
                        ->columnWidths([
                            'count' => '40px',
                            'product_id' => '180px',
                            'row_actions' => '20px',
                        ])
                        ->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->placeholder('select a product')
                                ->options(Product::pluck('name','id')),
                            TextInput::make('count')
                                ->numeric()
                                ->label('Sample count')
                                ->minValue(1),

                        ])
                        ->disableItemMovement()
                        ->defaultItems(1),
                    ])->compact(),
                Textarea::make('comment')
                    ->label('Comment')
                    ->columnSpan('full')
                    ->minLength('3')
                    ->required(),
        ])
        ->columns(3)];
    }
}
