<?php

namespace App\Filament\Resources\VisitResource\Forms;

use App\Models\CallType;
use App\Models\Client;
use App\Models\Product;
use App\Models\User;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Route;

class VisitForm
{
    public static function schema(): array
    {
        $isMedicalRep = auth()->user()?->hasRole('medical-rep');
        $isDailyVisits = str_contains(Route::current()->uri(), 'daily-visits');

        return [
            self::getUserSelect($isMedicalRep, $isDailyVisits),
            self::getSecondUserSelect(),
            self::getClientSelect($isDailyVisits),
            self::getCallTypeSelect(),
            self::getDatePickers(),
            self::getProductsSection(),
            self::getCommentField(),
        ];
    }

    /**
     * Get the medical rep select field.
     */
    private static function getUserSelect(bool $isMedicalRep, bool $isDailyVisits): Select
    {
        return Select::make('user_id')
            ->label('Medical Rep')
            ->searchable()
            ->relationship('user', 'name')
            ->placeholder('Search name')
            ->getSearchResultsUsing(fn (string $search) => User::getMine()
                ->where('name', 'like', "%{$search}%")
                ->limit(50)
                ->pluck('name', 'id'))
            ->options(User::getMine()->pluck('name', 'id'))
            ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
            ->disabled($isDailyVisits)
            ->hidden($isMedicalRep)
            ->preload();
    }

    /**
     * Get the second user (visit accompany) select field.
     */
    private static function getSecondUserSelect(): Select
    {
        return Select::make('second_user_id')
            ->label('Visit Accompany')
            ->relationship('secondRep', 'name')
            ->searchable()
            ->rules([
                function ($get) {
                    return function (string $attribute, $value, Closure $fail) use ($get) {
                        if ($value && $get('call_type_id') != CallType::where('name', 'Double')->value('id')) {
                            $fail("The Visit Accompany must be empty unless the call type is Double.");
                        }
                    };
                },
            ])
            ->required(fn($get) => $get('call_type_id') == CallType::where('name', 'Double')->value('id'))
            ->placeholder('Search name')
            ->getSearchResultsUsing(fn (string $search) => User::whereHas('roles', function($q) {
                $q->where('name', 'district-manager');
            })->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
            ->options(User::whereHas('roles', function($q) {
                $q->where('name', 'district-manager');
            })->pluck('name', 'id'))
            ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
            ->preload();
    }

    /**
     * Get the client select field.
     */
    private static function getClientSelect(bool $isDailyVisits): Select
    {
        return Select::make('client_id')
            ->label('Client')
            ->searchable()
            ->relationship('client', 'name')
            ->placeholder('Search by name or phone or speciality')
            ->disabled($isDailyVisits)
            ->getSearchResultsUsing(function(string $search) {
                return Client::inMyAreas()
                    ->where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('speciality', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->limit(50)
                    ->pluck('name_en', 'id');
            })
            ->options(Client::inMyAreas()->pluck('name_en', 'id'))
            ->getOptionLabelUsing(fn ($value): ?string => Client::find($value)?->name)
            ->preload()
            ->required(!$isDailyVisits);
    }

    /**
     * Get the call type select field.
     */
    private static function getCallTypeSelect(): Select
    {
        return Select::make('call_type_id')
            ->label('Call Type')
            ->options(CallType::all()->pluck('name', 'id'))
            ->preload()
            ->required();
    }

    /**
     * Get the date picker fields.
     */
    private static function getDatePickers(): array
    {
        return [
            DatePicker::make('next_visit')
                ->label('Next call time')
                ->closeOnDateSelection()
                ->minDate(today()->addDay()),
            DatePicker::make('visit_date')
                ->label('Visit Date')
                ->default(today()),
        ];
    }

    /**
     * Get the products section.
     */
    private static function getProductsSection(): Section
    {
        $isCreateOrEdit = str_contains(Route::current()->uri(), 'create') ||
                         str_contains(Route::current()->uri(), 'edit');

        return Section::make('products')
            ->hiddenLabel()
            ->schema([
                TableRepeater::make('products')
                    ->createItemButtonLabel('Add product')
                    ->relationship(function() use ($isCreateOrEdit) {
                        return $isCreateOrEdit ? 'nullRelation' : 'products';
                    })
                    ->hiddenLabel()
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
                            ->options(Product::pluck('name', 'id')),
                        TextInput::make('count')
                            ->numeric()
                            ->label('Sample count')
                            ->minValue(0),
                    ])
                    ->disableItemMovement()
                    ->defaultItems(1),
            ])
            ->compact();
    }

    /**
     * Get the comment textarea field.
     */
    private static function getCommentField(): Textarea
    {
        return Textarea::make('comment')
            ->label('Comment')
            ->columnSpan('full')
            ->minLength('3')
            ->required();
    }
}
