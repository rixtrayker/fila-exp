<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AreaBrick extends Model
{
    use HasFactory;

    protected $table = 'area_brick';

    protected $fillable = [
        'area_id',
        'brick_id',
    ];
}
