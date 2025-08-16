<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'bundle_id',
        'active',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'active' => 'boolean',
    ];

    public function bundle()
    {
        return $this->belongsTo(Bundle::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->where('active', true);
    }

    public function isActive()
    {
        return $this->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->where('active', true);
    }

    // public function distributions()
    // {
    //     return $this->hasMany(BundleDistribution::class);
    // }
}
