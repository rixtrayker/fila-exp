<?php

namespace App\Filament\Resources\OrderResource\Fields;

use Filament\Forms\Components\TextInput;

class SubTotalField
{
    /**
     * Create and return the sub-total field
     */
    public static function make(): TextInput
    {
        return TextInput::make('sub_total')
            ->disabled()
            ->default(0);
    }
}
