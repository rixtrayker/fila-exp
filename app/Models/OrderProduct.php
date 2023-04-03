<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'cost',
        'count',
        'order_id',
        'product_id',
        'created_at',
        'updated_at',
    ];
}
