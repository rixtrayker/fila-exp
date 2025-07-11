<?php

namespace App\Filament\Resources\OrderResource\Fields;

use App\Models\Client;
use Filament\Forms\Components\Select;

class ClientSelectField
{
    /**
     * Create and return the client select field
     */
    public static function make(): Select
    {
        return Select::make('client_id')
            ->label('Client')
            ->searchable()
            ->placeholder('Search by name or phone or speciality')
            ->getSearchResultsUsing(function(string $search) {
                return Client::where('name_en', 'like', "%{$search}%")
                    ->orWhere('name_ar', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhereHas('speciality', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->limit(50)
                    ->pluck('name_en', 'id');
            })
            ->options(Client::pharmacy()->pluck('name_en', 'id'))
            ->getOptionLabelUsing(fn ($value): ?string => Client::pharmacy()->find($value)?->name)
            ->preload()
            ->required();
    }
}
