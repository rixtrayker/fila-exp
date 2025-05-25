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

class FrequencyReportProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $queue = 'reports';

    protected $clientId;
    protected $date;
    protected $finalize;

    /**
     * Create a new job instance.
     */
    public function __construct(int $clientId, string $date, bool $finalize = false)
    {
        $this->clientId = $clientId;
        $this->date = $date;
        $this->finalize = $finalize;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logger = Log::channel('frequency_report');
        $logger->info('Updating frequency report data', [
            'client_id' => $this->clientId,
            'date' => $this->date
        ]);

        try {
            $client = Client::with(['clientType', 'brick.area'])->findOrFail($this->clientId);
            $date = Carbon::parse($this->date);

            // Calculate new data
            $now = now();
            $reportData = $this->calculateFrequencyDataForClientAndDate($client, $date);

            if ($reportData !== null) {
                // Update or create the record
                FrequencyReportData::updateOrCreateForDate(
                    $client->id,
                    $date->toDateString(),
                    $reportData
                );
            }

            $this->updateSyncTimestamp($now);

            $logger->info('Frequency report data updated successfully');

        } catch (\Exception $e) {
            $logger->error('Failed to update frequency report data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate frequency data for a specific client and date
     */
    private function calculateFrequencyDataForClientAndDate(Client $client, Carbon $date): ?array
    {
        // Get visits for the client on this date
        $visits = $client->visits()
            ->whereDate('visit_date', $date)
            ->get();

        // If no visits for this date, return null to skip
        if ($visits->isEmpty()) {
            return null;
        }

        // Calculate visit counts by status
        $doneVisits = $visits->where('status', 'visited')->count();
        $pendingVisits = $visits->whereIn('status', ['pending', 'planned'])->count();
        $missedVisits = $visits->where('status', 'cancelled')->count();
        $totalVisits = $visits->count();

        $achievementPercentage = $totalVisits > 0 ? ($doneVisits / $totalVisits) * 100 : 0.0;

        return [
            'client_name_en' => $client->name_en,
            'client_type_name' => $client->clientType?->name ?? null,
            'grade' => $client->grade,
            'brick_id' => $client->brick_id,
            'brick_name' => $client->brick?->name ?? null,
            'area_name' => $client->brick?->area?->name ?? null,
            'done_visits_count' => $doneVisits,
            'pending_visits_count' => $pendingVisits,
            'missed_visits_count' => $missedVisits,
            'total_visits_count' => $totalVisits,
            'achievement_percentage' => round($achievementPercentage, 2),
            'is_final' => !$date->isToday() || $this->finalize,
            'metadata' => [
                'sync_date' => now()->toISOString(),
                'visits_breakdown' => [
                    'visited' => $doneVisits,
                    'pending' => $visits->where('status', 'pending')->count(),
                    'planned' => $visits->where('status', 'planned')->count(),
                    'cancelled' => $missedVisits,
                ]
            ]
        ];
    }

    // update sync timestamp
    private function updateSyncTimestamp(Carbon $now)
    {
        Setting::updateFrequencyReportSyncTimestamp($now->timestamp);
    }
}
