<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacationRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function repUser()
    {
        return $this->belongsTo(User::class);
    }

    public function vacationType()
    {
        return $this->belongsTo(VacationType::class);
    }

    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new GetMineScope);
    }
}
