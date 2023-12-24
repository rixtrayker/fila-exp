<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Brick extends Model
{
    use HasFactory;
    protected $with = ['city.governorate.region.country'];
    // protected $appends = ['full_name'];
    // Todo: add caching
    protected $guarded = [];

    public function city() : BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function getFullNameAttribute() : string
    {
        return $this->name . ':' . $this->city->name;
    }

    public function area() : BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function getZoneCodeAttribute(){
        return $this->name.'-'.$this->city?->name
            .'-'.$this->city?->governorate?->name
            .'-'.$this->city?->governorate?->region?->name
            .'-'.$this->city?->governorate?->region?->country?->name;
    }
}
