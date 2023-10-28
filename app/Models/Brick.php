<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brick extends Model
{
    use HasFactory;
    protected $with = ['city.governorate.region.country'];
    protected $appends = ['full_name'];
    // Todo: add caching
    protected $guarded = [];
    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function getFullNameAttribute()
    {
        return $this->name . ':' . $this->city->name;
    }

    public function areas(){
        return $this->belongsToMany(Area::class);
    }

    public function getZoneCodeAttribute(){
        return $this->name.'-'.$this->city?->name
            .'-'.$this->city?->governorate?->name
            .'-'.$this->city?->governorate?->region?->name
            .'-'.$this->city?->governorate?->region?->country?->name;
    }
}
