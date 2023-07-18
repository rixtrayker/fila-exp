<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brick extends Model
{
    use HasFactory;
    protected $with = ['city'];

    protected $guarded = [];
    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function getZoneCodeAttribute(){
        return $this->name.'-'.$this->city?->name.'-'.$this->city?->governorate?->name.'-'.$this->region?->name.'-'.$this->country?->name;
    }
}
