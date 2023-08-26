<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BrickUser extends Model
{
    use HasFactory;

    protected $table = 'brick_user';

    protected $fillable = [
        'brick_id',
        'user_id',
    ];

    // public function setAreaIdAttribute($value){
    //     return;
    // }
}
