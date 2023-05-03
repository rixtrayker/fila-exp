<?php

namespace App\Filament\Templates\Visit;

use App\Models\CallType;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use App\Models\VisitType;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;

final class Regular
{
    public static function title()
    {
        return 'Regular';
    }

    public static function schema()
    {
        return [
            Fieldset::make('Regular Visit Fields')
            ->schema([
            Select::make('user_id')
                    ->label('Medical Rep')
                    ->searchable()
                    ->placeholder('Search name')
                    ->getSearchResultsUsing(fn (string $search) => User::role('medical-rep')->mine()->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->options(User::role('medical-rep')->mine()->pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->preload(),
                Select::make('second_user_id')
                    ->label('2nd Medical Rep')
                    ->searchable()
                    ->placeholder('Search name') //todo: search using speciality
                    ->getSearchResultsUsing(fn (string $search) => User::role('medical-rep')->mine()->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                    ->options(User::role('medical-rep')->mine()->pluck('name', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                    ->preload(),
                Select::make('client_id')
                    ->label('Client')
                    ->searchable()
                    ->placeholder('Search by name or phone or speciality')
                    ->getSearchResultsUsing(function(string $search){
                        return Client::where('name_en', 'like', "%{$search}%")
                            ->orWhere('name_ar', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhereHas('speciality', function ($q) use ($search) {
                                $q->where('name','like', "%{$search}%");
                            })->limit(50)->pluck('name_en', 'id');
                    })
                    ->options(Client::pluck('name_en', 'id'))
                    ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
                    ->preload()
                    ->required(),
                Select::make('call_type_id')
                    ->label('Call Type')
                    ->options(CallType::all()->pluck('name', 'id'))
                    ->preload()
                    ->required(),
                DatePicker::make('next_visit')
                    ->label('Next call time')
                    ->closeOnDateSelection()
                    ->minDate(today()->addDay())
                    ->required(),
                DatePicker::make('visit_date')
                    ->label('Visit Date')
                    ->default(today()),
                Section::make('products')
                    ->disableLabel()
                    ->schema([
                    TableRepeater::make('products')
                        ->relationship('products')
                        ->disableLabel()
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
                    ->required(),
        ])
        ->columns(3)];
    }
}
