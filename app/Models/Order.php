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

    function approve() {
        $this->approved = 1;
        $this->saveQuietly();
    }

    function decline() {
        $this->approved = -1;
        $this->saveQuietly();
    }

    function getApprovalAttribute(): bool {
        return $this->approved === 4;
    }

    function getProductListAttribute(): string {
        $products = $this->orderProducts;
        $list = [];
        foreach ($products as $product) {
            $list[] = $product->count.'x '.$product->product->name;
        }

        return implode(' , ',$list);
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
