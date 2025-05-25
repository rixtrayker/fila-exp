<?php

namespace App\Listeners;

use App\Events\FrequencyReportRequested;
// use App\Jobs\SyncFrequencyReportData;
// use App\Models\Setting;
// use Illuminate\Support\Facades\Log;
use App\Jobs\FrequencyReportProcess;

class HandleFrequencyReportRequest
{
    /**
     * Handle the event.
     */
    public function handle(FrequencyReportRequested $event): void
    {
        // // Check if report sync is enabled
        // $syncEnabled = Setting::getSetting('report_sync_enabled');
        // if (!$syncEnabled || !$syncEnabled->value) {
        //     return;
        // }

        // Log::channel('frequency_report')->info('Frequency report requested, dispatching sync job', [
        //     'from_date' => $event->fromDate,
        //     'to_date' => $event->toDate,
        //     'filters' => $event->filters
        // ]);

        // // Dispatch the sync job
        // SyncFrequencyReportData::dispatch(
        //     $event->fromDate,
        //     $event->toDate,
        //     false // not force sync
        // );

        FrequencyReportProcess::dispatch($event->fromDate, $event->toDate, $event->filters);
    }
}
