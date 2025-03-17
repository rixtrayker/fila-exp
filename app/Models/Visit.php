<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'visit_type_id',
        'call_type_id',
        'next_visit',
        'visit_date',
        'lat',
        'lng',
        'comment',
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

    public function visitType()
    {
        return $this->belongsTo(VisitType::class);
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
}
