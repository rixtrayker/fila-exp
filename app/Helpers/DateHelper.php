<?php

namespace App\Helpers;

use Carbon\Carbon;
class DateHelper{
    public static function getFirstOfWeek($nextWeek = false)
    {
        $today = today();

        // If today is a Saturday, use it as the start date
        if($today->isSaturday()) {
            return $today->copy()->startOfDay();
        }

        // Get the start date of the next Saturday if $nextWeek is true
        if($nextWeek) {
            return $today->next(Carbon::SATURDAY)->startOfDay();
        }

        // Get the start date of last Saturday
        return $today->copy()->previous(Carbon::SATURDAY)->startOfDay();
    }
    public static function calculateVisitDates(): array
    {
        $dates = [];

        // Get the start date of the current or next week depending on the current day
        $nextWeek = self::nextWeekRule();
        $startDate = self::getFirstOfWeek($nextWeek);

        // Loop through the next 7 days, starting from the start date
        for($i = 0; $i < 7; $i++) {
            $dates[] = $startDate->copy()->addDays($i)->format('Y-m-d'); // Add each day to the dates array
        }

        return $dates;
    }

    // True is next week
    private static function nextWeekRule(): bool{
        if(today()->isWednesday())
            return true;
        if(today()->isThursday())
            return true;
        if(today()->isFriday())
            return true;
        // return true if it's tuesday and after 10 pm
        return today()->isTuesday() && now()->isAfter(today()->addHours(22));
    }
}
