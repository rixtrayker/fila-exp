<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
class DateHelper{
    public static function getFirstOfWeek($nextWeek = false) : Carbon
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

    public static function today(): Carbon{
        if(now()->isBefore(today()->addHours(10))){
            return Carbon::yesterday();
        }
        return today();
    }

    public static function dayOfWeek(bool $oneBased = false): int{
        $today = (today()->dayOfWeek + 8) % 7;

        if($oneBased)
            return $today + 1;

        return $today;
    }

    public static function countWorkingDays($startDate, $endDate): float
    {
       // friday is off and half of thursday it counts  0.5
       // and saturday is stat of the week
       $startDate = Carbon::parse($startDate);
       $endDate = Carbon::parse($endDate);

       $days = 0;
       for($date = $startDate; $date->lte($endDate); $date->addDay()){
            if ($date->isFriday()) {
                continue;
            } elseif ($date->isThursday()) {
                $days += 0.5;
            } else {
                $days += 1;
            }
       }

       return $days;
    }
    private static function weekendsInRange($startDate, $endDate): array
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        $weekends = [];
        for($date = $startDate; $date->lte($endDate); $date->addDay()){
            if(self::isWeekend($date)){
                $weekends[] = $date->format('Y-m-d');
            }
        }
        return $weekends;
    }

    public static function cacheWeekends(Carbon $from, Carbon $to): void
    {
        $from = $from ?? Carbon::now()->startOfYear();
        $to = $to ?? Carbon::now()->endOfYear();

        if($from->isBefore(Cache::get('start_of_weekends')) && $to->isAfter(Cache::get('end_of_weekends'))){
            $from = Cache::get('start_of_weekends');
            $to = Cache::get('end_of_weekends');
            return;
        }

        $weekends = self::weekendsInRange($from, $to);

        $weekends = SortedStringSet::fromArray($weekends);
        Cache::put('year_of_weekends', $weekends);
        Cache::put('start_of_weekends', $weekends->getMin());
        Cache::put('end_of_weekends', $weekends->getMax());
    }

    public static function getWeekendInRange($startDate, $endDate): array
    {
        $startDate = Carbon::parse($startDate);
        $endDate = Carbon::parse($endDate);

        $weekends = Cache::get('year_of_weekends');
        if(!$weekends){
            self::cacheWeekends($startDate, $endDate);
            $weekends = Cache::get('year_of_weekends');
        }

        return $weekends->getElementsSorted($startDate, $endDate);
    }

    private static function isWeekend(Carbon $date): bool
    {
        return $date->isSaturday() || $date->isSunday();
    }
}
