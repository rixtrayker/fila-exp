<?php

namespace App\Helpers;

use Carbon\Carbon;
class DateHelper{

    public static function getFirstOfWeek($thisWeek = false)
    {
        $today = new Carbon();

        if($today->dayOfWeek == Carbon::SATURDAY){
            $date = $today;
        } else {
            if($thisWeek)
                $date = now()->next(Carbon::SATURDAY);
            else
                $date = new Carbon('last saturday');
        }

        return $date->format('Y-m-d');
    }
}
