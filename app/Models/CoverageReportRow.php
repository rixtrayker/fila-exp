<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoverageReportRow extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'id',
        'name', 
        'area_name',
        'working_days',
        'daily_visit_target',
        'monthly_visit_target',
        'office_work_count',
        'activities_count', 
        'actual_working_days',
        'sops',
        'actual_visits',
        'call_rate',
        'total_visits'
    ];

    protected $casts = [
        'working_days' => 'integer',
        'daily_visit_target' => 'integer', 
        'monthly_visit_target' => 'integer',
        'office_work_count' => 'integer',
        'activities_count' => 'integer',
        'actual_working_days' => 'integer',
        'sops' => 'decimal:2',
        'actual_visits' => 'integer', 
        'call_rate' => 'decimal:2',
        'total_visits' => 'integer'
    ];
}