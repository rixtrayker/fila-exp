<?php

namespace App\Services\Stats;

use App\Helpers\DateHelper;
use App\Models\Visit;
use Illuminate\Support\Collection;
use App\Traits\StatsHelperTrait;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Client;

class VisitStatsService
{
    use StatsHelperTrait;

    public function getVisitsQuery(): Builder
    {
        $startOfPlan = DateHelper::getFirstOfWeek();
        $endOfPlan = (clone $startOfPlan)->addDays(7);

        return Visit::query()
            ->select(['visit_date', 'status', 'plan_id'])
            ->whereIn('status', ['visited', 'pending'])
            ->whereDate('visit_date', '>=', $startOfPlan)
            ->whereDate('visit_date', '<=', $endOfPlan);
    }

    public function getVisits(): Collection
    {
        return $this->getVisitsQuery()->get();
    }

    public function getDailyPlanCoveredClients(): int
    {
        return $this->getVisitsQuery()
            ->whereNotNull('plan_id')
            ->select('client_id')
            ->distinct()
            ->count();
    }

    public function getClientsCount(): int
    {
        return Client::count();
    }
    public function getAchievedVisits(): string
    {
        $totalVisits = $this->getVisits()
            ->whereNotNull('plan_id')
            ->where('visit_date', DateHelper::today())
            ->count();

        $planDoneVisits = $this->getVisits()
            ->whereNotNull('plan_id')
            ->where('visit_date', DateHelper::today())
            ->where('status', 'visited')
            ->count();

        if ($planDoneVisits === 0) {
            return '0 %';
        }

        $percentage = $this->calculatePercentage($planDoneVisits, $totalVisits);
        return "$percentage %";
    }

    public function getDonePlanVisits(): int
    {
        return $this->getVisits()
            ->where('status', 'visited')
            ->count();
    }

    public function getVisitStats(): array
    {
        $actualVisits = $this->getVisits()
            ->where('status', 'visited')
            ->where('visit_date', DateHelper::today())
            ->count();

        $plannedVisits = $this->getVisits()
            ->where('status', 'pending')
            ->whereNotNull('plan_id')
            ->where('visit_date', DateHelper::today())
            ->count();

        if ($plannedVisits === 0) {
            return [
                'achievedRatio' => 0,
                'descriptionMessage' => "No planned visits",
                'color' => 'info',
                'actualVisits' => 0,
                'plannedVisits' => 0
            ];
        }

        $achievedRatio = $this->calculatePercentage($actualVisits, $plannedVisits);

        return [
            'achievedRatio' => $achievedRatio,
            'descriptionMessage' => "$actualVisits / $plannedVisits Done ($achievedRatio % of planned visits done)",
            'color' => $this->getStatsColor($achievedRatio),
            'actualVisits' => $actualVisits,
            'plannedVisits' => $plannedVisits
        ];
    }
}
