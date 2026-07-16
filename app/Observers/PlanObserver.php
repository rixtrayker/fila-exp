<?php

namespace App\Observers;

use App\Models\Plan;
use App\Models\PlanShift;
use App\Models\Visit;
use App\Services\PlanDataService;

class PlanObserver
{
    private $plan;

    /**
     * Handle the Plan "created" event.
     */
    public function created(Plan $plan): void
    {
        $this->plan = $plan;
        $planData = $plan->plan_data;
        $this->upsertVisits($planData);
        $this->upsertShifts($planData);
    }

    /**
     * Handle the Plan "updated" event.
     */
    public function updated(Plan $plan): void
    {
        $this->plan = $plan;
        $planData = $plan->plan_data;
        $this->upsertVisits($planData);
        $this->upsertShifts($planData);
    }

    /**
     * Handle the Plan "deleted" event.
     */
    public function deleted(Plan $plan): void
    {
        //
    }

    /**
     * Handle the Plan "restored" event.
     */
    public function restored(Plan $plan): void
    {
        //
    }

    /**
     * Handle the Plan "force deleted" event.
     */
    public function forceDeleted(Plan $plan): void
    {
        //
    }

    private function upsertVisits(array $planData)
    {
        $arr = [];
        $now = now();
        $userId = $this->plan->user_id;

        for ($i = 0; $i < 7; $i++) {
            $visitDate = $this->plan->start_at->copy()->addDays($i);
            $key = PlanDataService::DAYS[$i].'_clients';

            if (! isset($planData[$key])) {
                continue;
            }

            foreach ($planData[$key] as $client) {
                $arr[] = [
                    'user_id' => $userId,
                    'visit_date' => $visitDate,
                    'plan_id' => $this->plan->id,
                    'client_id' => $client,
                    'status' => $this->plan->approved ? 'pending' : 'planned',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        Visit::withoutGlobalScopes()
            ->where('plan_id', $this->plan->id)
            ->forceDelete();

        if ($arr !== []) {
            Visit::insert($arr);
        }
        // $visitIds = $this->plan->visits()->pluck('visits.id')->toArray();
        // $insertedVistis = collect($arr);
        // $deletingVists = [];
        // foreach($insertedVistis as $visit){

        // }

        // Visit::whereIn('id', $deletingVists)->delete();
    }

    private function upsertShifts(array $planData)
    {
        $arr = [];

        for ($i = 0; $i < 7; $i++) {
            $inputs = ['am_shift', 'pm_shift', 'am_time', 'pm_time'];
            $shiftData = [];

            foreach ($inputs as $input) {
                $key = PlanDataService::DAYS[$i].'_'.$input;
                $shiftData[$input] = $planData[$key] ?? null;
            }

            if (array_filter($shiftData, fn ($value) => $value !== null && $value !== '')) {
                $arr[] = array_merge($shiftData, [
                    'day' => $i + 1,
                    'plan_id' => $this->plan->id,
                ]);
            }
        }

        PlanShift::where('plan_id', $this->plan->id)->delete();

        if ($arr !== []) {
            PlanShift::upsert($arr, ['plan_id', 'day']);
        }

        $this->plan->unsetRelation('shifts');
        $this->plan->createShiftVisits();
    }
}
