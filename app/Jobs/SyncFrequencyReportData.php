<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Setting;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
class SyncFrequencyReportData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $queue = 'reports';

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
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logger = Log::channel('frequency_report');
        $logger->info('Starting frequency report sync', [
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'force_sync' => $this->forceSync
        ]);

        try {
            // Determine date range
            $fromDate = $this->fromDate ? Carbon::parse($this->fromDate) : $lastSyncDate;
            $toDate = $this->toDate ? Carbon::parse($this->toDate) : Carbon::now();

            if ($toDate->isFuture()) {
                $toDate = Carbon::today();
            }

            $logger->info('Processing date range', [
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString(),
                'last_sync_timestamp' => $lastSyncTimestamp
            ]);

            // Get all clients
            $clients = Client::with(['clientType', 'brick.area'])->get();
            $processedRecords = 0;

            foreach ($clients as $client) {
                $currentDate = $fromDate->copy();
                while ($currentDate <= $toDate) {
                    FrequencyReportProcess::dispatch($client->id, $currentDate->toDateString(), $this->forceSync);
                    $processedRecords++;

                    $currentDate->addDay();
                }
            }
        } catch (\Exception $e) {
            $logger->error('Frequency report sync failed', [
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
        Log::channel('frequency_report')->error('Frequency report sync job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}
