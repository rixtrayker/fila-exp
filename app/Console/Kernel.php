<?php

namespace App\Console;

use App\Jobs\CancelMissedVisitsJob;
use App\Jobs\OptimizeAppPerformance;
use App\Jobs\FetchOfficialHolidays;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;
use App\Jobs\SyncFrequencyReportData;
use App\Jobs\SyncCoverageReportData;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        try {
            $schedule->job(new CancelMissedVisitsJob)->daily()->at('10:01');
        } catch (\Exception $e) {
            Log::channel('daily')->info("job scheduling failed, " . $e->getMessage());
        }

        $schedule->job(new OptimizeAppPerformance)->daily()->at('00:00');
        $schedule->job(new FetchOfficialHolidays)->monthlyOn(1, '00:00');


        // $schedule->job(new SyncCoverageReportData(today()->subDay(), today()->subDay(), true))->daily()->at('02:00');
        // $schedule->job(new SyncFrequencyReportData(today()->subDay(), today()->subDay(), true))->daily()->at('02:00');

        // Clear old coverage report cache daily at 10:00 AM
        // $schedule->command('coverage:clear-old-cache')->daily()->at('10:00');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
