<?php

namespace App\Filament\Resources\OrderResource\Helpers;

use App\Models\Product;

class TotalsCalculator
{
    /**
     * Update totals based on product changes or discount changes
     *
     * @param callable $get The getter function for retrieving state values
     * @param callable $set The setter function for setting state values
     * @param bool $isFromProducts Flag indicating if the update was triggered from within products repeater
     */
    public static function updateTotals($get, $set, $isFromProducts = true)
    {
        // Calculate subtotal from products
        $subTotal = self::calculateSubtotal($get, $isFromProducts);

        // Set the subtotal using the appropriate path
        self::setFieldValue($set, 'sub_total', $subTotal, $isFromProducts);

        // Calculate final total after discount
        $total = self::calculateTotalAfterDiscount($get, $subTotal, $isFromProducts);

        // Set the total using the appropriate path
        self::setFieldValue($set, 'total', $total, $isFromProducts);
    }

    /**
     * Calculate subtotal based on products in the form
     *
     * @param callable $get The getter function
     * @param bool $isFromProducts Whether the function is called from within products
     * @return float The calculated subtotal
     */
    public static function calculateSubtotal($get, $isFromProducts = true)
    {
        // Get products based on context
        $products = self::getProductsArray($get, $isFromProducts);

        $subTotal = 0;
        foreach ($products as $item) {
            if (empty($item['product_id']) || empty($item['count'])) {
                continue;
            }

            $product = Product::find($item['product_id']);
            if ($product) {
                $subTotal += $product->price * $item['count'];
            }
        }

        return $subTotal;
    }

    /**
     * Calculate total after applying discount
     *
     * @param callable $get The getter function
     * @param float $subTotal The calculated subtotal
     * @param bool $isFromProducts Whether the function is called from within products
     * @return float The total after discount
     */
    public static function calculateTotalAfterDiscount($get, $subTotal, $isFromProducts = true)
    {
        // Get discount type and amount with proper path prefixing
        $discountType = self::getFieldValue($get, 'discount_type', $isFromProducts) ?? 'value';
        $discount = (float)(self::getFieldValue($get, 'discount', $isFromProducts) ?? 0);

        $total = $subTotal;

        if ($discountType == 'percentage') {
            $total = $total - ($total * ($discount / 100));
        } else {
            $total = $total - $discount;
        }

        // Ensure total is not negative
        return max(0, $total);
    }

    /**
     * Get field value with proper path resolution based on context
     *
     * @param callable $get The getter function
     * @param string $fieldName The field name to get
     * @param bool $isFromProducts Whether the function is called from within products
     * @return mixed The field value
     */
    public static function getFieldValue($get, $fieldName, $isFromProducts = true)
    {
        $path = $isFromProducts ? "../../{$fieldName}" : $fieldName;
        return $get($path);
    }

    /**
     * Set field value with proper path resolution based on context
     *
     * @param callable $set The setter function
     * @param string $fieldName The field name to set
     * @param mixed $value The value to set
     * @param bool $isFromProducts Whether the function is called from within products
     */
    public static function setFieldValue($set, $fieldName, $value, $isFromProducts = true)
    {
        $path = $isFromProducts ? "../../{$fieldName}" : $fieldName;
        $set($path, $value);
    }

    /**
     * Get products array with proper path resolution based on context
     *
     * @param callable $get The getter function
     * @param bool $isFromProducts Whether the function is called from within products
     * @return array The products array
     */
    public static function getProductsArray($get, $isFromProducts = true)
    {
        $path = $isFromProducts ? '../../products' : 'products';
        return $get($path) ?? [];
    }
}