<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeWork extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'time_from',
        'time_to',
        'status',
    ];

    // protected $casts = [
    //     'time_from' => 'datetime',
    //     'time_to' => 'datetime',
    // ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
