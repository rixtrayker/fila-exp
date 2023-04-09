<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use \Znck\Eloquent\Traits\BelongsToThrough;
class City extends Model
{
    use HasFactory;
    use BelongsToThrough;
    protected $fillable = [
        'name',
        'governorate_id',
    ];

    protected $with = ['governorate','region'];
    // protected $appends = ['zone_code'];

    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }

    public function region()
    {
        return $this->belongsToThrough(Region::class, Governorate::class);
    }
    public function country()
    {
        return $this->belongsToThrough(Country::class, [Region::class,Governorate::class]);
    }
    public function clients()
    {
        return $this->hasMany(Client::class);
    }
    public function users()
    {
        return $this->belongsToMany(User::class);
    }
    public function visits()
    {
        return $this->hasManyThrough(Visit::class, Client::class);
    }
    public function getZoneCodeAttribute(){
        return $this->name.'-'.$this->governorate->name.'-'.$this->region->name.'-'.$this->country->name;
    }
}
