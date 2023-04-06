<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'user_id',
        'client_id',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class,'order_products');
    }

    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new GetMineScope);
    }
}
