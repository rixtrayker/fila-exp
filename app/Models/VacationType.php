<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VacationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'consumes_annual_entitlement',
    ];

    protected $casts = [
        'consumes_annual_entitlement' => 'boolean',
    ];
}
