<?php

namespace App\Models;

use App\Traits\HasEditRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Client extends Model
{
    use HasFactory;
    use HasEditRequest;
    use HasRelationships;

    protected $appends = ['name'];
    protected $fillable = [
        'name_en',
        'name_ar',
        'email',
        'phone',
        'address',
        'brick_id',
        'grade',
        'shift',
        'related_pharmacy',
        'am_work',
        'client_type_id',
        'speciality_id',
    ];
    public $editable = [
        'name_en',
        'name_ar',
        'email',
        'phone',
        'address',
        'brick_id',
        'grade',
        'shift',
        'related_pharmacy',
        'am_work',
        'client_type_id',
        'speciality_id',
    ];

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
    public function getVisitsCountAttribute()
    {
        return $this->visits()->count();
    }
    public function getMissedVisitsCountAttribute()
    {
        return $this->visits()->where('status','cancelled')->count();
    }
    public function getPendingVisitsCountAttribute()
    {
        return $this->visits()->where('status','pending')->count();
    }
    public function getDoneVisitsCountAttribute()
    {
        return $this->visits()->where('status','visited')->count();
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
            $ids += auth()->user()->bricks()->pluck('bricks.id');
        }

        return $builder->whereIn('brick_id', $ids);
    }
}
