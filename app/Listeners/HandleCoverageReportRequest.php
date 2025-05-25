<?php

namespace App\Listeners;

use App\Events\CoverageReportRequested;
// use App\Jobs\SyncCoverageReportData;
// use App\Models\Setting;
// use Illuminate\Support\Facades\Log;
use App\Jobs\CoverageReportProcess;

class HandleCoverageReportRequest
{
    /**
     * Handle the event.
     */
    public function handle(CoverageReportRequested $event): void
    {
        // $syncEnabled = Setting::getSetting('report_sync_enabled');
        // if (!$syncEnabled || !$syncEnabled->value) {
        //     return;
        // }

        // Log::channel('coverage_report')->info('Coverage report requested, dispatching sync job', [
        //     'from_date' => $event->fromDate,
        //     'to_date' => $event->toDate,
        //     'filters' => $event->filters
        // ]);

        // // Dispatch the sync job
        // SyncCoverageReportData::dispatch(
        //     $event->fromDate,
        //     $event->toDate,
        //     false // not force sync
        // );

        CoverageReportProcess::dispatch($event->fromDate, $event->toDate, $event->filters);
    }
}
