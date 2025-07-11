<?php

namespace App\Filament\Resources\VisitResource\Forms;

use App\Models\CallType;
use App\Models\Client;
use App\Models\ClientType;
use App\Models\Product;
use App\Models\User;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Route;

class VisitForm
{
    protected static $clients;
    protected static $clientOptions;
    protected static $clientTypes;

    public static function boot()
    {
        static::$clients = Client::inMyAreas()
            ->select('id', 'name_en', 'client_type_id')
            ->get()
            ->keyBy('id');

        static::$clientTypes = ClientType::pluck('name', 'id');
        static::$clientOptions = self::$clients->mapWithKeys(function ($client) {
            return [$client->id => $client->name_en];
        });
    }

    public static function schema(): array
    {
        // dd the uri
        self::boot();
        $isMedicalRep = auth()->user()?->hasRole('medical-rep');
        $isDailyVisits = str_contains(Route::current()->uri(), 'daily-visits');

        return [
            self::getUserSelect($isMedicalRep, $isDailyVisits),
            self::getSecondUserSelect(),
            self::getClientSelect($isDailyVisits),
            self::getCallTypeSelect(),
            ...self::getDatePickers(),
            self::getProductsSection(),
            self::getFeedbackField(),
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
            ->options(self::$clientOptions)
            ->getOptionLabelUsing(fn ($value): ?string => self::getClientName($value))
            ->preload()
            ->reactive()
            ->required(!$isDailyVisits);
    }

    /**
     * Get the call type select field.
     */
    private static function getCallTypeSelect(): Select
    {
        return Select::make('call_type_id')
            ->label('Call Type')
            ->options(CallType::all()->pluck('name', 'id')->toArray())
            ->default(1)
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
                ->disabled(str_contains(request()->url(), 'visits/edit'))
                ->default(today()),
        ];
    }

    private static function isViewPage(): bool
    {
        $url = request()->url();
        $uri = Route::current()->uri();
        $referer = request()->headers->get('referer');
        $isLivewireUpdate = str_contains($url, 'livewire/update');

        if ($isLivewireUpdate) {
            if (str_contains($referer, 'admin/visits/create') ||
                str_contains($referer, 'admin/visits/edit') ||
                str_contains($referer, 'admin/daily-visits/edit')) {
                return false;
            }
        }
        return ! (
            str_contains($uri, 'create') ||
            str_contains($uri, 'edit') && !str_contains($uri, 'daily-visits')
        );
    }

    private static function getProductsSection(): Section
    {
        $repeater = self::getProductsRepeater();
        if (self::isViewPage()) {
            $repeater->relationship('products');
        }

        return Section::make('products')
            ->hiddenLabel()
            ->schema([$repeater])
            ->compact();
    }

    /**
     * Get the products section.
     */
    private static function getProductsRepeater(): TableRepeater
    {
        return  TableRepeater::make('products')
            ->addActionLabel('Add product')
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
                    ->label('Samples')
                    ->minValue(0),
            ])
            ->reorderable(false)
            ->defaultItems(1);
    }

    /**
     * Get the feedback select field.
     */
    private static function getFeedbackField(): Select
    {
        return Select::make('feedback')
            ->label('Feedback')
            ->options(function ($get) {
                $clientId = $get('client_id');
                return self::getFeedbackOptions($clientId);
            })
            ->required();
    }

    /**
     * Get the comment textarea field.
     */
    private static function getCommentField(): Textarea
    {
        return Textarea::make('comment')
            ->label('Comment')
            ->columnSpan('full')
            ->minLength('3');
    }
    private static function getClientName(?int $clientId): ?string
    {
        if (!$clientId) {
            return null;
        }
        return self::$clients->where('id', $clientId)->first()?->name_en;
    }

    private static function getFeedbackOptions(?int $clientId): array
    {
        $feedbackOptions = [
            'default' => [
                'Aware' => 'Aware',
                'Un Aware' => 'Un Aware',
                'Promise to prescribe' => 'Promise to prescribe',
                'Prescribing' => 'Prescribing',
                'Advocate' => 'Advocate',
            ],
            'pharmacy' => [
                'Available' => 'Available',
                'Not Available' => 'Not Available',
                'Not Interested' => 'Not Interested',
                'Requested' => 'Requested',
            ],
        ];

        if (!$clientId) {
            return $feedbackOptions['default'];
        }

        $client = self::$clients->where('id', $clientId)->first();
        if (!$client) {
            return $feedbackOptions['default'];
        }

        if ($client->client_type_id === static::$clientTypes->search('Pharmacy')) {
            return $feedbackOptions['pharmacy'];
        }

        return $feedbackOptions['default'];
    }
}
