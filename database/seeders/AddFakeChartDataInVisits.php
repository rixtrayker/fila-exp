<?php

namespace Database\Seeders;

use App\Models\Visit;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddFakeChartDataInVisits extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $dates = $this->getDates();

        Visit::factory()
            ->count(count($dates))
            ->sequence(fn ($sequence) => ['visit_date' => $dates[$sequence->index]])
            ->create();
    }
    private function getDates()
    {
        $dates = [];
        $months = $this->getYearPeriod();

        foreach($months as $date){

            for($i=0; $i<random_int(1,28); $i++) {
                $dates[] = $date->addDays(random_int(1,28));
            }
        }

        return $dates;
    }

    private function getYearPeriod(){
        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();

        $period = $startOfMonth->subMonths(12)->monthsUntil($endOfMonth);

        return $period;
    }
}
