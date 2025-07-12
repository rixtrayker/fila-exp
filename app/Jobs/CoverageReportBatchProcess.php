<?php

namespace App\Jobs;

use App\Models\Reports\CoverageReportData;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CoverageReportBatchProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 1800; // 30 minutes timeout for batch processing
    public $memory = 1024; // Increased memory limit

    protected $userIds;
    protected $fromDate;
    protected $toDate;
    protected $forceSync;

    /**
     * Create a new job instance.
     */
    public function __construct(array $userIds, string $fromDate, string $toDate, bool $forceSync = false)
    {
        $this->userIds = $userIds;
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
        $logger->info('Starting batch coverage report processing', [
            'user_count' => count($this->userIds),
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

            // Get users with their relationships
            $users = User::with(['area'])
                ->whereIn('id', $this->userIds)
                ->get()
                ->keyBy('id');

            $processedRecords = 0;

            // Process each user
            foreach ($this->userIds as $userId) {
                $user = $users->get($userId);
                
                if (!$user) {
                    $logger->warning('User not found', ['user_id' => $userId]);
                    continue;
                }

                $result = $this->processUserDate($user, $fromDate, $toDate, $logger);
                $processedRecords += $result ?? 0;
            }

            $logger->info('Batch processing completed', [
                'total_records_processed' => $processedRecords,
                'users_processed' => count($this->userIds)
            ]);

        } catch (\Exception $e) {
            $logger->error('Batch coverage report processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_ids' => $this->userIds
            ]);
            throw $e;
        }
    }

    /**
     * Process coverage data for a specific user and date range.
     */
    private function processUserDate(User $user, Carbon $fromDate, Carbon $toDate, Log $logger): ?int
    {
        try {
            // Get visits for the user within the date range
            $visits = $user->visits()
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
                ];

                // Update or create the record
                CoverageReportData::updateOrCreateForDate(
                    $user->id,
                    $date->toDateString(),
                    $reportData
                );
                $processedRecords++;
            }

            return $processedRecords;

        } catch (\Exception $e) {
            $logger->error('Failed to process user date', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            // Don't throw - continue with other dates/users
            return 0;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('coverage_report')->error('Coverage report batch processing job failed', [
            'user_ids' => $this->userIds,
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);
    }
}