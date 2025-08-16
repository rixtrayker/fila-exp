<?php

namespace App\Models;

use App\Helpers\DateHelper;
use App\Models\Scopes\GetMineScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Events\VisitsEvents\VisitUpdated;
use App\Events\VisitsEvents\VisitCreated;

class Visit extends Model
{
    use HasFactory;
    use SoftDeletes;
    use \Znck\Eloquent\Traits\BelongsToThrough;

    protected $casts = [
        'visit_date' => 'date'
    ];
    protected $fillable = [
        'user_id',
        'plan_id',
        'status',
        'place',
        'atendees_number',
        'second_user_id',
        'client_id',
        'call_type_id',
        'next_visit',
        'visit_date',
        'lat',
        'lng',
        'comment',
        'feedback',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function clientType()
    {
        return $this->belongsToThrough(ClientType::class, Client::class);
    }

    public function scopeVisited(Builder $query): Builder
    {
        return $query->where('status','visited');
    }
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status','pending');
    }
    public function scopeDaily(Builder $query): Builder
    {
        return $query->where('status','pending')->where(function($q) {
            $q->whereDate('visit_date',today())->orWhere(function($q) {
                if(now()->isBefore(today()->addHours(10)))
                    $q->whereDate('visit_date',today()->subDay());
            });
        });
    }
    public function scopeMissed(Builder $query): Builder
    {
        return $query->where('status','cancelled');
    }
    public function scopeAll(Builder $query): Builder
    {
        return $query->where(column:'status',operator:'!=',value:'planned');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function secondRep()
    {
        return $this->belongsTo(User::class, 'second_user_id');
    }


    public function callType()
    {
        return $this->belongsTo(CallType::class);
    }
    public function brick()
    {
        return $this->belongsToThrough(Brick::class, Client::class);
    }
    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
    public function products()
    {
        return $this->belongsToMany(Product::class,'product_visits');
    }
    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new GetMineScope);
    }

    public function nullRelation(): HasOne
    {
        return new HasOne($this->newQuery(), $this, 'id', '', '');
    }

    public function changeDate($date)
    {
        $this->update(['visit_date' => $date]);
    }
    public function swapable()
    {
        return $this->plan->lastOfPlan < today();
    }

    public static function plannedVisitsNextWeekQuery(Builder $query): Builder
    {
        $dates = DateHelper::calculateVisitDates();

        return $query
            ->whereIn('visit_date', $dates)
            ->where('status', 'planned');
    }

    public static function plannedVisitsForDistrictManager(Builder $query): Builder
    {
        $myId = auth()->id() ?? 0;
        $medicalReps = User::where('parent_id', $myId)->pluck('id');
        return self::plannedVisitsNextWeekQuery($query)->whereIn('user_id', $medicalReps);
    }

    public static function districtManagerClients(): Collection
    {
        $visits = self::plannedVisitsForDistrictManager(self::query())->with('client')->get();
        $visits->each(callback: function($visit) {
            $day = Carbon::parse($visit->visit_date)->format('D');
            $visit->day = Str::lower($day);
        });

        return $visits;
    }

    protected static function booted()
    {
        static::updated(function ($visit) {
            event(new VisitUpdated($visit));
        });
        static::created(function ($visit) {
            event(new VisitCreated($visit));
        });
    }
}
