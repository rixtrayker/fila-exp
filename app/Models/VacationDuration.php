<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DateTime;

class VacationDuration extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function VacationReguest()
    {
        return $this->belongsTo(VacationRequest::class);
    }

    // duration from am to pm same day is 1
    // duration from am to am same day is 0.5
    // and so on handle stqart from pm and ends at pm same day is 1
    // handle start from pm and ends at am next day is 0.5
    // handle start from am and ends at pm next day is 0.5
    // handle start from am and ends at am next day is 1
    // handle start from pm and ends at pm next day is 1

    public function getDurationAttribute()
    {
        $sum = 0;

        // Convert start and end into DateTime objects
        $date1 = new \DateTime($this->start);
        $date2 = new \DateTime($this->end);

        // Get difference in full days
        $interval = $date1->diff($date2);
        $diffInDays = $interval->days;
        $sum += $diffInDays;

        // Add extra half or full day based on shift values
        if ($this->start_shift === $this->end_shift) {
            $sum += 0.5;
        } elseif ($this->start_shift === 'AM' && $this->end_shift === 'PM') {
            $sum += 1;
        }

        return $sum;
    }


    public function getDaysAttribute()
    {
        $date1 = new DateTime($this->start);
        $date2 = new DateTime($this->end);
        $interval = $date1->diff($date2);
        return $interval->days;
    }

}
