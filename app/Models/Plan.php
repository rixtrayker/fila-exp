<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use App\Services\PlanDataService;
use App\Traits\CanApprove;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class Plan extends Model
{
    use CanApprove;
    use HasFactory;

    protected $casts = [
        'start_at' => 'date',
        'plan_data' => 'json',
    ];

    protected $fillable = [
        'user_id',
        'start_at',
        'plan_data',
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

    public function getEndDateAttribute()
    {
        return $this->start_at->copy()->addDays(6);
    }

    public function shiftClient($shiftQuery)
    {
        $dayIndex = array_search($shiftQuery, PlanDataService::DAYS, true);

        if ($dayIndex === false) {
            return null;
        }

        $shift = $this->shifts()->where('day', $dayIndex + 1)->first();

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
        static::addGlobalScope(new GetMineScope);
        // after updated approved add clear cache of visit stats
        static::updated(function ($plan) {
            Cache::forget('visit_stats_daily_plan_'.$plan->user_id);
        });
        parent::boot();
    }

    public function lastDay(): Carbon
    {
        return $this->start_at->copy()->addDays(6);
    }

    public function createShiftVisits()
    {
        $shifts = $this->shifts;

        foreach ($shifts as $shift) {
            $visitDate = $this->start_at->copy()->addDays($shift->day - 1);

            foreach ([$shift->am_shift, $shift->pm_shift] as $clientId) {
                if (! $clientId) {
                    continue;
                }

                Visit::withoutGlobalScopes()->updateOrCreate(
                    [
                        'plan_id' => $this->id,
                        'visit_date' => $visitDate,
                        'client_id' => $clientId,
                    ],
                    [
                        'user_id' => $this->user_id,
                        'status' => $this->approved ? 'pending' : 'planned',
                        'deleted_at' => null,
                    ],
                );
            }
        }
    }
}
