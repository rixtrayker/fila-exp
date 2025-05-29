<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncCoverageReportData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    protected $fromDate;
    protected $toDate;
    protected $forceSync;

    /**
     * Create a new job instance.
     */
    public function __construct($fromDate = null, $toDate = null, $forceSync = false)
    {
        $this->fromDate = $fromDate;
        $this->toDate = $toDate;
        $this->forceSync = $forceSync;
        $this->onQueue('reports');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logger = Log::channel('coverage_report');
        $logger->info('Starting coverage report sync', [
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'force_sync' => $this->forceSync
        ]);

        try {
            // Get last sync timestamp
            $defaultFromDate = Carbon::parse('2022-01-01');
            // Determine date range
            $fromDate = $this->fromDate ? Carbon::parse($this->fromDate) : $defaultFromDate;
            $toDate = $this->toDate ? Carbon::parse($this->toDate) : Carbon::now();

            // if toDate is in the future, set it to today
            if ($toDate->isFuture()) {
                $toDate = Carbon::today();
            }

            $logger->info('Processing date range', [
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString(),
            ]);

            // Get medical reps and district managers
            $users = User::role(['medical-rep', 'district-manager'])->get();
            $processedRecords = 0;

            foreach ($users as $user) {
                // Process each day in the range
                $currentDate = $fromDate->copy();
                while ($currentDate <= $toDate) {
                    // Dispatch job for this user and date
                    CoverageReportProcess::dispatch($user->id, $currentDate->toDateString(), $this->forceSync);
                    $processedRecords++;

                    $currentDate->addDay();
                }
            }

            // Update sync timestamp
            Setting::updateCoverageReportSyncTimestamp($toDate->timestamp);

            $logger->info('Coverage report sync completed', [
                'processed_records' => $processedRecords,
                'new_sync_timestamp' => $toDate->timestamp
            ]);

        } catch (\Exception $e) {
            $logger->error('Coverage report sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('coverage_report')->error('Coverage report sync job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
