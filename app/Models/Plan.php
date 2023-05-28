<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $casts = [
        'start_at' => 'date'
    ];
    protected $fillable = [
        'user_id',
        'start_at',
        'approved',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
    public function shifts()
    {
        return $this->hasMany(PlanShift::class);
    }

    public function getEndDateAttribute(){
        return $this->start_at->addDays(6);
    }
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new GetMineScope);
    }

    public function shiftClient($shiftQuery){
        $days = ['sat','sun','mon','tues','wednes','thurs','fri'];
        $day = array_search($shiftQuery, $days) + 1;

        $shift = $this->shifts()->where('day', $day)->first();

        return $shift;
    }

    public function approve()
    {
        $this->approved = 1;
        $this->visits()->update(['status' => 'pending']);
        $this->save();
    }

    public function reject()
    {
        $this->delete();
    }
}
