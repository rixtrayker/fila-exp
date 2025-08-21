<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProduct extends Model
{
    use HasFactory;
    protected $with = ['product'];
    protected $fillable = [
        'cost',
        'count',
        'item_total',
        'market_price',
        'discount_percentage',
        'discount_value',
        'order_id',
        'product_id',
        'created_at',
        'updated_at',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
