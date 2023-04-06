<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Visit extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'second_user_id',
        'client_id',
        'visit_type_id',
        'call_type_id',
        'next_visit',
        'visit_date',
        'comment',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
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

    public function city()
    {
        return $this->belongsTo(City::class);
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
}
