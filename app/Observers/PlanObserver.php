<?php

namespace App\Observers;

use App\Models\Plan;
use App\Models\PlanShift;
use App\Models\Visit;
use Illuminate\Support\Arr;

class PlanObserver
{
    private $days = ['sat', 'sun', 'mon', 'tues', 'wednes', 'thurs', 'fri'];
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

    private function upsertVisits(array $planData){
        $arr = [];
        $now = now();
        $userId = $this->plan->user_id;

        for ($i = 0; $i < 7; $i++) {
            $visitDate = $this->plan->start_at->addDays($i);
            $key = $this->days[$i] . '_clients';

            if(!isset($planData[$key]))
                continue;

            foreach($planData[$key] as $client){
                $arr[] = [
                    'user_id' => $userId,
                    'visit_date' => $visitDate,
                    'plan_id' => $this->plan->id,
                    'client_id' => $client,
                    'visit_type_id' => 1,
                    'status' => $this->plan->approved ? 'pending' : 'planned',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        Visit::where('plan_id', $this->plan->id)->delete();
        Visit::upsert($arr, ['plan_id', 'client_id', 'visit_date']);
        // $visitIds = $this->plan->visits()->pluck('visits.id')->toArray();
        // $insertedVistis = collect($arr);
        // $deletingVists = [];
        // foreach($insertedVistis as $visit){

        // }

        // Visit::whereIn('id', $deletingVists)->delete();
    }

    private function upsertShifts(array $planData){
        $arr = [];

        for ($i = 0; $i < 7; $i++) {
            $inputs = ['am_shift','pm_shift','am_time','pm_time'];
            $temp = [];
            for ($j = 0; $j < 4; $j++) {
                $key = $this->days[$i] .'_'. $inputs[$j];
                if(isset($planData[$key]))
                    $temp[$inputs[$j]] = $planData[$key];
            }

            $temp = array_filter($temp);
            if($temp){
                $temp['day'] = $i + 1;
                $temp['plan_id'] = $this->plan->id;
                $arr[] = $temp;
            }
        }

        PlanShift::upsert($arr, ['plan_id', 'day']);
        $this->plan->createShiftVisits();
    }
}
