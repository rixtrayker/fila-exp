<?php

namespace App\Models;

use App\Traits\HasEditRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Client extends Model
{
    use HasFactory;
    use HasTranslations;
    use HasEditRequest;

    protected $translatable = ['name'];
    protected $fillable = [
        'name',
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
        'name',
        'email',
        'phone',
        'address',
        'city_id',
        'grade',
        'shift',
        'client_type_id',
        'speciality_id',
    ];
    protected $casts = [
        'name' => 'json',
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
    public function getArabicNameAttribute()
    {
        return $this->getTranslation('name', 'ar');
    }
    public function setArabicNameAttribute($value){

        $this->setTranslation('name','ar',$value)->save();
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
}
