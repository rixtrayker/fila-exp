<?php

namespace App\Filament\Resources\VisitResource\Widgets;

use App\Helpers\DateHelper;
use App\Models\Order;
use App\Models\VacationRequest;
use App\Models\Visit;
use DateTime;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Collection;

class StatsOverview extends BaseWidget
{
    private static $visits;

    protected function getCards(): array
    {
        return [
            Card::make('Daily achieved vists', $this->achievedVisits())
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
            Card::make('Done plan visits', $this->donePlanVisits()),
            Card::make('Direct orders', $this->directOrders()),
            Card::make('Remaining vacations', $this->remainingVacations()),
        ];
    }

    private function achievedVisits(): string
    {
        $totalVisits = self::getVisits()
            ->whereNotNull('plan_id')
            ->where('visit_date', DateHelper::today())
            ->count();

        $planDoneVisits = self::getVisits()
            ->whereNotNull('plan_id')
            ->where('visit_date', DateHelper::today())
            ->where('status', 'visited')
            ->count();

        if($planDoneVisits === 0)
            return '0 %';

        $percentage = ($planDoneVisits / $totalVisits) * 100;

        $achievedRatio = round($percentage, 2);

        return "$achievedRatio %";
    }

    private function donePlanVisits(): int
    {
        return self::getVisits()
            ->where('status','visited')
            ->count();
    }

    private function directOrders(): int
    {
       return Order::query()
            ->where('created_at', today())
            ->where('approved', '>', 0)
            ->count();
    }

    private function remainingVacations()
    {
        $vacations = 0;
        $userVacations = VacationRequest::query()
            ->with('vacationDurations')
            ->where('created_at','>=',today()->firstOfYear())
            ->where('user_id',auth()->id())
            ->where('approved','>',0)
            ->get();

        foreach ($userVacations as $vacation) {
            $requestDurations =  $vacation->vacationDurations;
            foreach( $requestDurations as $duration){
                $date1 = new DateTime($duration->start);
                $date2 = new DateTime($duration->end);
                $interval = $date1->diff($date2);
                $diffInDays = $interval->days;
                $vacations += $diffInDays;

                if($duration->start_shift === $duration->end_shift){
                    $vacations += 0.5;
                }

                if($duration->start_shift == 'AM' && $duration->end_shift == 'PM'){
                    $vacations += 1;
                }
            }
        }

        return 21 - $vacations;
    }
    private static function getVisits(): Collection{
        if(self::$visits){
            return self::$visits;
        }

        $startOfPlan = DateHelper::getFirstOfWeek();
        $endOfPlan = (clone $startOfPlan)->addDays(7);

        self::$visits = Visit::query()
            ->select(['visit_date','status','plan_id'])
            ->whereIn('status', ['visited', 'pending'])
            ->whereDate('visit_date', '>=', $startOfPlan)
            ->whereDate('visit_date', '<=', $endOfPlan)
            ->get();

        return self::$visits;
    }
}
