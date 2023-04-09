<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    use HasFactory;

    protected $fillable = [
        'name',
        'country_id',
    ];

    public function governorates(){
        return $this->hasMany(Governorate::class);
    }
    public function country(){
        return $this->belongsTo(Country::class);
    }

    public function cities(){
        return $this->hasManyThrough(City::class, Governorate::class);
    }

    public function clients(){
        return $this->hasManyDeepFromRelations($this->cities(), (new City())->clients());
    }

    public function visits(){
        return $this->hasManyDeepFromRelations($this->clients(), (new Client())->visits());
    }
}
