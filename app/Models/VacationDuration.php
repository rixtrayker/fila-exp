<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacationDuration extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function VacationReguest()
    {
        return $this->belongsTo(VacationRequest::class);
    }
}
