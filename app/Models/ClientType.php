<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientType extends Model
{
    use HasFactory;

    const HOSPITAL = 1;
    const CLINIC = 2;
    const POLY_CLINIC = 3;
    const PHARMACY = 4;
    const INCUBATORS_CENTRE = 5;
    const RESUSCITATION_CENTRE = 6;
    const DISTRIBUTOR = 7;

    protected $fillable = [
        'name',
    ];
}
