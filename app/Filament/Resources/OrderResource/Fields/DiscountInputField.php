<?php

namespace App\Filament\Resources\OrderResource\Fields;

use App\Filament\Resources\OrderResource\Helpers\TotalsCalculator;
use Filament\Forms\Components\TextInput;

class DiscountInputField
{
    /**
     * Create and return the discount input field
     */
    public static function make(): TextInput
    {
        return TextInput::make('discount')
            ->label('Discount')
            ->reactive()
            ->numeric()
            ->minValue(0)
            ->maxValue(function($get) {
                return $get('discount_type') == 'percentage' ? 100 : $get('sub_total');
            })
            ->placeholder(function($get) {
                $type = $get('discount_type');
                $suffix = $type == 'percentage' ? ' %' : '';
                return "please enter discount {$type}{$suffix}";
            })
            ->hidden(auth()->user()->hasRole('medical-rep'))
            ->afterStateUpdated(function($get, $set) {
                TotalsCalculator::updateTotals($get, $set, false);
            });
    }
}
