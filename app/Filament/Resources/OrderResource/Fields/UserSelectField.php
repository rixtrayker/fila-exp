<?php

namespace App\Filament\Resources\OrderResource\Fields;

use App\Models\User;
use Filament\Forms\Components\Select;

class UserSelectField
{
    /**
     * Create and return the user select field
     */
    public static function make(): Select
    {
        return Select::make('user_id')
            ->label('Medical Rep')
            ->searchable()
            ->placeholder('Search name')
            ->getSearchResultsUsing(fn (string $search) =>
                User::where('name', 'like', "%{$search}%")
                    ->limit(50)
                    ->pluck('name', 'id')
            )
            ->options(User::pluck('name', 'id'))
            ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
            ->preload()
            ->hidden(auth()->user()->hasRole('medical-rep'));
    }
}
