<?php

namespace App\Filament\Resources\VisitResource\Widgets;

use App\Models\VacationRequest;
use App\Models\Visit;
use DateTime;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;

class StatsOverview extends BaseWidget
{
      protected function getCards(): array
    {
        return [
            Card::make('Daily achieved vists', $this->achievedVisits()),
            Card::make('Total visits', $this->totalVisits()),
            Card::make('Direct orders', $this->directOrders()),
            Card::make('Remaining vacations', $this->remainingVacations()),
        ];
    }

    private function achievedVisits()
    {
        $totalVisits = Visit::where('visit_date', today())->whereIn('status',['verified','visited'])->count();

        if($totalVisits == 0)
            return '0 %';

        $doneVisits = Visit::where('visit_date', today())->where('status','visited')->count();

        $percentage = ($doneVisits / $totalVisits) % 100;

        return round($percentage, 2);
    }

    private function totalVisits()
    {
        $totalVisits = Visit::where('visit_date', today())->whereIn('status',['verified','visited'])->count();
        return $totalVisits;
    }
    private function directOrders() // todo
    {
        $totalVisits = Visit::where('visit_date', today())->whereIn('status',['verified','visited'])->count();
        return $totalVisits;
    }

    private function remainingVacations()
    {
        $vacations = 0;
        $userVacations = VacationRequest::where('rep_id',auth()->id())->where('approved',true)->get();

        foreach ($userVacations as $vacation) {
            $date1 = new DateTime($vacation->start);
            $date2 = new DateTime($vacation->end);
            $interval = $date1->diff($date2);
            $diffInDays = $interval->days;
            $vacations += $diffInDays;
        }

        return 21 - $vacations;
    }
}
