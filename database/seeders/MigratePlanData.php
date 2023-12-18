<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\PlanShift;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class MigratePlanData extends Seeder
{
    use WithoutModelEvents;
    /**
     * Run the database seeds.
     */
    private $days = ['sat', 'sun', 'mon', 'tues', 'wednes', 'thurs', 'fri'];

    public function run(): void
    {
        $plans = Plan::with(['visits','shifts'])->get();

        foreach($plans as $plan){
            $planData = [
                ...$this->getShiftsData($plan->shifts),
                ...$this->getVisitsData($plan->visits, $plan),
            ];

            logger()->channel('debugging')->info(json_encode($this->getVisitsData($plan->visits, $plan)));
            $plan->update(['plan_data' => array_filter($planData)]);
        }


    }

    private function getShiftsData(Collection $planShifts):array
    {
        $arr = [];
        $keys = ['am_shift','pm_shift','am_time','pm_time'];
        foreach($planShifts as $shift){
            for($i=0; $i<4;$i++){
                $key = $this->days[$shift->day - 1].'_'.$keys[$i];
                $value = $shift->{$keys[$i]};
                if($value){
                    $arr[$key] = $value;
                }
            }
        }
        return $arr;
    }
    private function getVisitsData(Collection $visits, $plan): array
    {
        $arr = [];
        foreach($visits as $visit){
            $dayKey = $this->getDay($visit->visit_date, $plan->start_at) % 7;

            $day = $this->days[$dayKey];
            $key = $day.'_clients';

            if(!isset($arr[$key])){
                $arr[$key] = [];
            }

            $arr[$key][] = $visit->client_id;
        }

        foreach($arr as $key => $value){
            $arr[$key] = array_values(array_unique(array_filter($value)));
        }

        return $arr;
    }

    private function getDay(Carbon $visitDate, Carbon $planStart): int
    {
        return $visitDate->diff($planStart)->days;
    }
}
