<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientType extends Model
{
    use HasFactory;

    // const HOSPITAL = 1;
    // const CLINIC = 4;
    // const POLY_CLINIC = 5;
    // const PHARMACY = 6;
    // const INCUBATORS_CENTRE = 7;
    // const RESUSCITATION_CENTRE = 8;
    // const DISTRIBUTOR = 9;
    const AM = 3;
    const PM = 1;
    const PH = 2;

    protected $fillable = ["name"];
}
