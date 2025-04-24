<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use App\Traits\CanApprove;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacationRequest extends Model
{
    use HasFactory;
    use CanApprove;
    protected $guarded = [];

    public function repUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function vacationType()
    {
        return $this->belongsTo(VacationType::class);
    }
    public function vacationDurations()
    {
        return $this->hasMany(VacationDuration::class);
    }

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new GetMineScope);
    }

    public function getStartAtAttribute()
    {
        $vacationDuration =  $this->vacationDurations()->first();
        return $vacationDuration->start;
    }
    public function getEndAtAttribute()
    {
        $vacationDuration =  $this->vacationDurations()->latest()->first();
        return $vacationDuration->end;
    }

    public function getDurationAttribute()
    {
        $vacationDuration =  $this->vacationDurations;
        $sum = 0;
        // handle 0.5 of day vacationm
        foreach($vacationDuration as $duration){
            $sum += $duration->duration;
        }
        return $sum;
    }
}
