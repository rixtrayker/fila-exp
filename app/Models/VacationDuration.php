<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTime;
use App\Services\VacationCalculator;

class VacationDuration extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $fillable = [
        'vacation_request_id',
        'start_shift',
        'end_shift',
        'start',
        'end',
        'duration'
    ];

    public function vacationRequest()
    {
        return $this->belongsTo(VacationRequest::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            $calculator = app(VacationCalculator::class);
            $model->duration = $calculator->calculateTotalDuration(
                $model->start,
                $model->end,
                $model->start_shift,
                $model->end_shift
            );
        });

        static::updating(function ($model) {
            $calculator = app(VacationCalculator::class);
            $model->duration = $calculator->calculateTotalDuration(
                $model->start,
                $model->end,
                $model->start_shift,
                $model->end_shift
            );
        });
    }

    public function getDurationAttribute()
    {
        if ($this->attributes['duration'] === null) {
            $calculator = app(VacationCalculator::class);
            return $calculator->calculateTotalDuration(
                $this->start,
                $this->end,
                $this->start_shift,
                $this->end_shift
            );
        }
        return $this->attributes['duration'];
    }

    public function getTotalDaysAttribute()
    {
        $date1 = new DateTime($this->start);
        $date2 = new DateTime($this->end);
        $interval = $date1->diff($date2);
        return $interval->days;
    }
}
