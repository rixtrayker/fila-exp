<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use App\Traits\CanApprove;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;
    use CanApprove;

    protected $fillable = [
        'user_id',
        'client_id',
        'discount_type',
        'sub_total',
        'total',
        'approved',
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class,'order_products');
    }

    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class);
    }

    function getApprovalAttribute(): bool {
        return $this->approved === 4;
    }

    function getProductListAttribute(): string {
        $products = $this->orderProducts()->with('product')->get();
        $list = [];
        foreach ($products as $product) {
            $list[] = $product->count.' x '.$product->product?->name;
        }

        return implode(' , ',$list);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new GetMineScope);
    }
}
