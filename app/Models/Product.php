<?php

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use Filament\Forms\Components\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'product_category_id',
        'price',
        'active',
    ];

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new ActiveScope);
    }
    // public function scopeActive(Builder $query): Builder
    // {
    //     return  $query->where('active', true);
    // }

    public function productCategory()
    {
        return  $this->belongsTo(ProductCategory::class);
    }
    public function orders()
    {
        return $this->belongsToMany(Order::class,'order_products');
    }
    public function visits()
    {
        return $this->belongsToMany(Visit::class,'order_visits');
    }
}
