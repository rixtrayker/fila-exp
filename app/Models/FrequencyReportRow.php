<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FrequencyReportRow extends Model
{
    public $timestamps = false;

    protected $primaryKey = 'client_id';
    public $incrementing = false;

    protected $fillable = [
        'client_id',
        'client_name',
        'client_type_name',
        'brick_name',
        'done_visits_count',
        'pending_visits_count',
        'missed_visits_count',
        'total_visits_count',
        'achievement_percentage'
    ];

    protected $casts = [
        'client_id' => 'integer',
        'done_visits_count' => 'integer',
        'pending_visits_count' => 'integer',
        'missed_visits_count' => 'integer',
        'total_visits_count' => 'integer',
        'achievement_percentage' => 'decimal:2'
    ];
}
