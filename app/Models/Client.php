<?php

namespace App\Models;

use App\Traits\HasEditRequest;
use Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Client extends Model
{
    use HasFactory;
    use HasEditRequest;
    use HasRelationships;

    protected $appends = ['name', 'mapUrl', 'location'];
    protected $fillable = [
        'name_en',
        'name_ar',
        'email',
        'phone',
        'address',
        'location',
        'brick_id',
        'grade',
        'shift',
        'related_pharmacy',
        'am_work',
        'client_type_id',
        'speciality_id',
        'lat',
        'lng',
    ];
    public $editable = [
        'name_en',
        'name_ar',
        'email',
        'phone',
        'address',
        // 'location',
        'brick_id',
        'grade',
        'shift',
        'related_pharmacy',
        'am_work',
        'client_type_id',
        'speciality_id',
        'lat',
        'lng',
    ];

    public function getLocationAttribute()
    {
        return json_decode($this->location, true);
    }

    public function setLocationAttribute($value)
    {
        $this->attributes['location'] = json_encode($value);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function visitedBy()
    {
        return $this->hasManyDeepFromRelations($this->visits(), (new Visit())->user());
    }
    public function brick()
    {
        return $this->belongsTo(Brick::class);
    }
    public function clientType()
    {
        return $this->belongsTo(ClientType::class);
    }
    public function speciality()
    {
        return $this->belongsTo(Speciality::class);
    }
    public function getNameAttribute()
    {
        return $this->name_en .' - '. $this->name_ar;
    }

    public function mapUrl(): string|null
    {
        if (!$this->lat || !$this->lng) {
            return null;
        }
        return 'https://www.google.com/maps/place/'. $this->lat . ',' . $this->lng;
    }

    public function getMapUrlAttribute(): string|null
    {
        return $this->mapUrl();
    }

    public function setLocation($value){
        $this->lat = $value['lat'];
        $this->lng = $value['lng'];
        $this->location = $value;
        $this->save();
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->whereJsonContains('name', $search);
        });
        // ->when($filters['status'] ?? null, function ($query, $status) {
        //     $query->where('status', '=', $status);
        // });
    }
    public function clientRequests()
    {
        return $this->hasMany(ClientRequest::class);
    }

    public function scopeInMyAreas($builder)
    {
        if(!auth()->user()){
            return;
        }

        if(auth()->user()->hasRole('super-admin')){
            return $builder;
        }

        $myAreas = auth()->user()->areas;
        $ids = [];

        foreach($myAreas as $area){
            $ids = $ids + $area->bricks()->pluck('bricks.id')->toArray();
        }

        if(auth()->user()->hasRole('medical-rep')){
            $ids += auth()->user()->bricks()->pluck('bricks.id')->toArray();
        }

        return $builder->whereIn('brick_id', $ids);
    }
}
