<?php

namespace App\Filament\Resources\OrderResource\Fields;

use Filament\Forms\Components\TextInput;

class TotalField
{
    /**
     * Create and return the total field
     */
    public static function make(): TextInput
    {
        return TextInput::make('total')
            ->disabled()
            ->default(0)
            ->hidden(auth()->user()->hasRole('medical-rep'));
    }
}
