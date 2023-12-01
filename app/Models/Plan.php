<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use App\Traits\CanApprove;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Plan extends Model
{
    use HasFactory;
    use CanApprove;

    protected $casts = [
        'start_at' => 'date'
    ];
    protected $fillable = [
        'user_id',
        'start_at',
        'approved',
        'approved_at',
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

    public function shiftClient($shiftQuery){
        $days = ['sat','sun','mon','tues','wednes','thurs','fri'];
        $day = array_search($shiftQuery, $days) + 1;

        $shift = $this->shifts()->where('day', $day)->first();

        return $shift;
    }

    public function approvePlan()
    {
        $this->approve();
        $this->visits()->update(['status' => 'pending']);
    }

    public function rejectPlan()
    {
        $this->reject();
        $this->delete();
    }

    protected static function boot()
    {
        static::created(fn($plan) => self::createShiftVisits($plan));
        static::updated(fn($plan) => self::createShiftVisits($plan));

        static::addGlobalScope(new GetMineScope);

        parent::boot();
    }

    public function lastDay(): Carbon {
        return $this->start_at->addDays(6);
    }

    public static function createShiftVisits($plan){
        $shifts = $plan->shifts;

        foreach($shifts as $shift){
            $visitDate = Carbon::createFromDate($plan->start_at)->addDays($shift->day - 1);

            // insert AM client & PM client
            Visit::upsert([
                [
                    'user_id' => $plan->user_id,
                    'client_id' => $shift->am_shift,
                    'visit_type_id' => 1,
                    'plan_id' => $plan->id,
                    'status' => 'planned',
                    'visit_date' => $visitDate,
                ],
                [
                    'user_id' => $plan->user_id,
                    'client_id' => $shift->pm_shift,
                    'visit_type_id' => 1,
                    'plan_id' => $plan->id,
                    'status' => 'planned',
                    'visit_date' => $visitDate,
                ]
            ], ['plan_id', 'visit_date','client_id']);
        }
    }
}
