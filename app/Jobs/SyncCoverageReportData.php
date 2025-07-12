<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SyncCoverageReportData implements ShouldQueue
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
        $logger = Log::channel('coverage_report');
        $logger->info('Starting coverage report sync', [
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

            // Get all users in chunks to avoid memory issues
            $userIds = User::pluck('id')->toArray();
            $processedRecords = 0;
            $chunkSize = 50; // Process 50 users at a time

            $logger->info('Processing users in chunks', [
                'total_users' => count($userIds),
                'chunk_size' => $chunkSize
            ]);

            // Process users in batches to avoid dispatching too many jobs
            foreach (array_chunk($userIds, $chunkSize) as $userChunk) {
                CoverageReportBatchProcess::dispatch($userChunk, $fromDate->toDateString(), $toDate->toDateString(), $this->forceSync);
                $processedRecords += count($userChunk);
                
                $logger->info('Dispatched batch job', [
                    'users_in_batch' => count($userChunk),
                    'total_processed' => $processedRecords
                ]);
            }
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