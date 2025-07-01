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
    public $timeout = 600; // 10 minutes timeout
    public $memory = 512;

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
        $logger = Log::channel('frequency_report');
        $logger->info('Starting frequency report sync', [
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'force_sync' => $this->forceSync
        ]);

        try {
            // Determine date range
            $defaultFromDate = Carbon::parse('2022-01-01');
            $fromDate = $this->fromDate ? Carbon::parse($this->fromDate) : $defaultFromDate;
            $toDate = $this->toDate ? Carbon::parse($this->toDate) : Carbon::now();

            if ($toDate->isFuture()) {
                $toDate = Carbon::today();
            }

            $logger->info('Processing date range', [
                'from_date' => $fromDate->toDateString(),
                'to_date' => $toDate->toDateString(),
            ]);

            // Get all clients in chunks to avoid memory issues
            $clientIds = Client::pluck('id')->toArray();
            $processedRecords = 0;
            $chunkSize = 50; // Process 50 clients at a time

            $logger->info('Processing clients in chunks', [
                'total_clients' => count($clientIds),
                'chunk_size' => $chunkSize
            ]);

            // Process clients in batches to avoid dispatching too many jobs
            foreach (array_chunk($clientIds, $chunkSize) as $clientChunk) {
                FrequencyReportBatchProcess::dispatch($clientChunk, $fromDate->toDateString(), $toDate->toDateString(), $this->forceSync);
                $processedRecords += count($clientChunk);
                
                $logger->info('Dispatched batch job', [
                    'clients_in_batch' => count($clientChunk),
                    'total_processed' => $processedRecords
                ]);
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
