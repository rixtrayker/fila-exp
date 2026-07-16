<?php

namespace App\Jobs;

use App\Helpers\DateHelper;
use App\Models\Visit;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class CancelMissedVisitsJob
{
    public function __construct(private ?CarbonInterface $cutoffDate = null)
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $cutoffDate = $this->cutoffDate ?? DateHelper::today();
        $missedVisitDate = CarbonImmutable::instance($cutoffDate)->subDay();

        Visit::withoutGlobalScopes()
            ->whereNotNull('plan_id')
            ->where('status', 'pending')
            ->whereDate('visit_date', $missedVisitDate)
            ->update(['status' => 'cancelled']);
    }
}
