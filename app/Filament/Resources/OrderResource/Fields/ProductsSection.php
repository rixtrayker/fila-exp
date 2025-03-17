<?php

namespace App\Filament\Resources\OrderResource\Fields;

use App\Filament\Resources\OrderResource\Helpers\TotalsCalculator;
use Filament\Forms\Components\Section;

class ProductsSection
{
    /**
     * Create and return the products section with all its fields
     */
    public static function make(): Section
    {
        return Section::make('products')
            ->hiddenLabel()
            ->schema([
                ProductsTableRepeater::make(),
                DiscountTypeField::make(),
                DiscountInputField::make(),
                SubTotalField::make(),
                TotalField::make(),
            ])
            ->compact()
            ->columns(4);
    }
}
