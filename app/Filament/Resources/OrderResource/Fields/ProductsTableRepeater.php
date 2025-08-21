<?php

namespace App\Filament\Resources\OrderResource\Fields;

use App\Filament\Resources\OrderResource\Helpers\TotalsCalculator;
use App\Models\Product;
use Awcodes\FilamentTableRepeater\Components\TableRepeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class ProductsTableRepeater
{
    /**
     * Create and return the products table repeater component
     */
    public static function make(): TableRepeater
    {
        return TableRepeater::make('products')
            ->relationship('products')
            ->reactive()
            ->hiddenLabel()
            ->headers(['Product', 'Quantity'])
            ->emptyLabel('There is no product added.')
            ->columnWidths([
                'count' => '140px',
                'cost' => '140px',
                'price' => '140px',
                'market_price' => '140px',
                'discount_percentage' => '140px',
                'discount_value' => '140px',
                'product_id' => '440px',
                'row_actions' => '20px',
            ])
            ->schema([
                Select::make('product_id')
                    ->hiddenLabel()
                    ->placeholder('select a product')
                    ->options(Product::pluck('name', 'id'))
                    ->required()
                    ->searchable()
                    ->reactive()
                    ->afterStateUpdated(function($set, $get) {
                        $product = Product::find($get('product_id'));
                        $price = $product ? $product->price : 0;
                        $marketPrice = $product ? ($product->market_price ?? 0) : 0;
                        $discountPercentage = $product ? ($product->discount_percentage ?? 0) : 0;
                        $discountValue = $product ? ($product->discount_value ?? 0) : 0;
                        $count = $get('count') ?: 0;
                        $cost = $product ? $price * $count : 0;

                        $set('price', $price);
                        $set('market_price', $marketPrice);
                        $set('discount_percentage', $discountPercentage);
                        $set('discount_value', $discountValue);
                        $set('cost', $cost);
                        $set('item_total', $cost);
                        TotalsCalculator::updateTotals($get, $set);
                    }),
                TextInput::make('count')
                    ->numeric()
                    ->required()
                    ->minValue(0)
                    ->hiddenLabel()
                    ->reactive()
                    ->afterStateUpdated(function($set, $get) {
                        $product = Product::find($get('product_id'));
                        $count = $get('count') ?: 0;
                        $cost = $product ? $product->price * $count : 0;

                        $set('item_total', $cost);
                        $set('cost', $cost);
                        TotalsCalculator::updateTotals($get, $set);
                    }),
                TextInput::make('price')
                    ->numeric()
                    ->minValue(1)
                    ->disabled()
                    ->hiddenLabel()
                    ->reactive(),
                TextInput::make('market_price')
                    ->numeric()
                    ->minValue(0)
                    ->disabled()
                    ->hiddenLabel()
                    ->reactive(),
                TextInput::make('discount_percentage')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(100)
                    ->disabled()
                    ->hiddenLabel()
                    ->reactive(),
                TextInput::make('discount_value')
                    ->numeric()
                    ->minValue(0)
                    ->disabled()
                    ->hiddenLabel()
                    ->reactive(),
                TextInput::make('item_total')
                    ->label('Item total')
                    ->dehydrateStateUsing(function($get) {
                        $product = Product::find($get('product_id'));
                        $count = $get('count') ?: 0;
                        return $product ? $product->price * $count : 0;
                    })
                    ->numeric()
                    ->disabled()
                    ->hiddenLabel(),
            ])
            ->afterStateUpdated(function($set, $get) {
                TotalsCalculator::updateTotals($get, $set);
            })
            ->deleteAction(function($set, $get) {
                TotalsCalculator::updateTotals($get, $set, false);
            })
            ->columnSpanFull()
            ->defaultItems(1);
    }
}
