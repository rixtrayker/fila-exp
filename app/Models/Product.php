<?php

namespace App\Models;

use App\Models\Scopes\ActiveScope;
use Filament\Forms\Components\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Product extends Model
{
    use HasFactory;
    use HasTranslations;

    protected $fillable = [
        'name',
        'product_category_id',
        'price',
        'active',
    ];
    protected $translatable = ['name'];

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new ActiveScope);
    }
    public function scopeActive(Builder $query): Builder
    {
        return  $query->where('active', true);
    }
    public function getArabicNameAttribute()
    {
        return $this->getTranslation('name', 'ar');
    }
    public function productCategory()
    {
        return  $this->belongsTo(ProductCategory::class);
    }
}
