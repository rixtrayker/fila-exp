<?php

namespace App\Listeners;

use App\Events\VisitsEvents\VisitEvent;
use App\Jobs\CoverageReportProcess;
use App\Jobs\FrequencyReportProcess;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateReportData implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Handle the event.
     */
    public function handle(VisitEvent $event): void
    {
        $visit = $event->visit;
        $date = $visit->visit_date;
        $created = $event->created;

        // Only update current date's data
        if ($date->isToday()) {
            // Update coverage report for the user
            CoverageReportProcess::dispatch($visit->user_id, $date->toDateString());

            // Update frequency report for the client
            FrequencyReportProcess::dispatch($visit->client_id, $date->toDateString());
        }
    }
}
