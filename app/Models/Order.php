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

    public function productsWithPivot   ()
    {
        return $this->belongsToMany(Product::class,'order_products')
                    ->withPivot('count', 'cost','item_total','market_price','discount_percentage','discount_value');
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

    public function scopeInMyAreas($builder)
    {
        if(!auth()->user()){
            return;
        }

        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        if(!($user?->hasRole(['accountant']))){
            return $builder;
        }

        $myAreas = auth()->user()->areas;
        $ids = [];

        foreach($myAreas as $area){
            $ids = $ids + $area->bricks()->pluck('bricks.id')->toArray();
        }


        return $builder->join('clients','clients.id','=','orders.client_id')
            ->whereIn('clients.brick_id',$ids)
            ->select('orders.*');
    }
}
