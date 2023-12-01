<?php

namespace App\Jobs;

use App\Models\Plan;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CancelMissedVisitsJob
{
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $closedPlans = Plan::whereDate('start_at',today()->subDays(8))->get();
        // $cutoffDate = today()->addDays(7)->addHours(10);

        foreach($closedPlans as $plan){
            $plan->pendingVisits()->update(['status' => 'cancelled']);
        }
    }
}
