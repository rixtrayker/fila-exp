<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'governorate_id',
    ];

    public function governorate()
    {
        return $this->belongsTo(Governorate::class);
    }

    public function clients()
    {
        return $this->hasMany(Client::class);
    }


    public function visits()
    {
        return $this->hasManyThrough(Visit::class, Client::class);
    }
}
