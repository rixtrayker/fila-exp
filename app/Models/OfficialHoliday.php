<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'description',
        'country_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
