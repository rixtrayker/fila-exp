<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacationRequest extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function repUser()
    {
        return $this->belongsTo(User::class,'rep_id');
    }

    public function vacationType()
    {
        return $this->belongsTo(VacationType::class);
    }
}
