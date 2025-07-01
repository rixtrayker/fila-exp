<?php

namespace App\Jobs;

use App\Models\Setting;
use App\Models\Reports\FrequencyReportData;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FrequencyReportBatchProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 1800; // 30 minutes timeout for batch processing
    public $memory = 1024; // Increased memory limit

    protected $clientIds;
    protected $fromDate;
    protected $toDate;
    protected $forceSync;

    /**
     * Create a new job instance.
     */
    public function __construct(array $clientIds, string $fromDate, string $toDate, bool $forceSync = false)
    {
        $this->clientIds = $clientIds;
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
        $logger->info('Starting batch frequency report processing', [
            'client_count' => count($this->clientIds),
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'force_sync' => $this->forceSync
        ]);

        try {
            $fromDate = Carbon::parse($this->fromDate);
            $toDate = Carbon::parse($this->toDate);

            if ($toDate->isFuture()) {
                $toDate = Carbon::today();
            }

            // Get clients with their relationships
            $clients = Client::with(['clientType', 'brick.area'])
                ->whereIn('id', $this->clientIds)
                ->get()
                ->keyBy('id');

            $processedRecords = 0;
            $totalDays = $fromDate->diffInDays($toDate) + 1;

            $logger->info('Processing batch', [
                'total_days' => $totalDays,
                'clients_found' => $clients->count()
            ]);

            // Process each client
            foreach ($this->clientIds as $clientId) {
                $client = $clients->get($clientId);

                if (!$client) {
                    $logger->warning('Client not found', ['client_id' => $clientId]);
                    continue;
                }

                $clientProcessedDays = 0;

                $result = $this->processClientDate($client, $fromDate, $toDate, $logger);
                $processedRecords += $result ?? 0;

                $logger->info('Completed client processing', [
                    'client_id' => $clientId,
                    'days_processed' => $clientProcessedDays
                ]);
            }

            $logger->info('Batch processing completed', [
                'total_records_processed' => $processedRecords,
                'clients_processed' => count($this->clientIds)
            ]);

        } catch (\Exception $e) {
            $logger->error('Batch frequency report processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'client_ids' => $this->clientIds
            ]);
            throw $e;
        }
    }

    /**
     * Process frequency data for a specific client and date
     */
    private function processClientDate(Client $client, Carbon $fromDate, Carbon $toDate, Log $logger): ?int
    {
        try {
            // Get visits for the client within the date range
            $visits = $client->visits()
                ->withoutGlobalScopes()
                ->whereBetween('visit_date', [$fromDate, $toDate])
                ->get();

            // If no visits, no records to process
            if ($visits->isEmpty()) {
                return 0;
            }

            // Get distinct dates from the visits
            $distinctDates = $visits->pluck('visit_date')->map(function ($date) {
                return $date->toDateString();
            })->unique();

            $processedRecords = 0;

            foreach ($distinctDates as $dateString) {
                $date = Carbon::parse($dateString);
                $visitsOnDate = $visits->where('visit_date', $date);

                if ($visitsOnDate->isEmpty()) {
                    continue;
                }

                $doneVisits = $visitsOnDate->where('status', 'visited')->count();
                $pendingVisits = $visitsOnDate->whereIn('status', ['pending', 'planned'])->count();
                $missedVisits = $visitsOnDate->where('status', 'cancelled')->count();
                $totalVisits = $visitsOnDate->count();

                $achievementPercentage = $totalVisits > 0 ? ($doneVisits / $totalVisits) * 100 : 0.0;

                $reportData = [
                    'done_visits_count' => $doneVisits,
                    'pending_visits_count' => $pendingVisits,
                    'missed_visits_count' => $missedVisits,
                    'total_visits_count' => $totalVisits,
                    'achievement_percentage' => round($achievementPercentage, 2),
                    'is_final' => !$date->isToday(),
                    'metadata' => [
                        'sync_date' => now()->toISOString(),
                        'visits_breakdown' => [
                            'visited' => $doneVisits,
                            'pending' => $visitsOnDate->where('status', 'pending')->count(),
                            'planned' => $visitsOnDate->where('status', 'planned')->count(),
                            'cancelled' => $missedVisits,
                        ]
                    ]
                ];

                // Update or create the record
                FrequencyReportData::updateOrCreateForDate(
                    $client->id,
                    $date->toDateString(),
                    $reportData
                );
                $processedRecords++;
            }

            return $processedRecords;

        } catch (\Exception $e) {
            $logger->error('Failed to process client date', [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);
            // Don't throw - continue with other dates/clients
            return 0;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('frequency_report')->error('Frequency report batch processing job failed', [
            'client_ids' => $this->clientIds,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}