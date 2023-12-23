<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'name'
    ];

    public function bricks(){
        return $this->hasMany(Brick::class);
    }

    public function getBricksNamesAttribute(){
        return join(" | ", $this->bricks->pluck('full_name')->toArray());
    }
}
