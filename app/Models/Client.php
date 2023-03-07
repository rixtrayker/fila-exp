<?php

namespace App\Models;

use App\Traits\HasEditRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
    use HasEditRequest;

    protected $appends = ['name'];
    protected $fillable = [
        'name_en',
        'name_ar',
        'email',
        'phone',
        'address',
        'city_id',
        'grade',
        'shift',
        'client_type_id',
        'speciality_id',
    ];
    public $editable = [
        'name_en',
        'name_ar',
        'email',
        'phone',
        'address',
        'city_id',
        'grade',
        'shift',
        'client_type_id',
        'speciality_id',
    ];

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
    public function city()
    {
        return $this->belongsTo(City::class);
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
}
