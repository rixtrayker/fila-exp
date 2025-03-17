<?php

namespace App\Filament\Resources\OrderResource\Fields;

use App\Filament\Resources\OrderResource\Helpers\TotalsCalculator;
use Filament\Forms\Components\Select;

class DiscountTypeField
{
    /**
     * Create and return the discount type select field
     */
    public static function make(): Select
    {
        return Select::make('discount_type')
            ->options(['percentage' => 'Percentage', 'value' => 'Value'])
            ->default('value')
            ->reactive()
            ->label('Discount Type')
            ->hidden(auth()->user()->hasRole('medical-rep'))
            ->afterStateUpdated(function($get, $set) {
                TotalsCalculator::updateTotals($get, $set, false);
            });
    }
}
