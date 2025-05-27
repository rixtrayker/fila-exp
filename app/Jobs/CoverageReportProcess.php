<?php

namespace App\Jobs;

use App\Models\Reports\CoverageReportData;
use App\Models\User;
use App\Models\Visit;
use App\Models\Activity;
use App\Models\OfficeWork;
use App\Models\VacationDuration;
use App\Helpers\DateHelper;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CoverageReportProcess implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    protected $userId;
    protected $date;
    protected $finalize;

    /**
     * Create a new job instance.
     */
    public function __construct(int $userId, string $date, bool $finalize = false)
    {
        $this->userId = $userId;
        $this->date = $date;
        $this->finalize = $finalize;
        $this->onQueue('reports');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logger = Log::channel('coverage_report');
        $logger->info('Updating coverage report data', [
            'user_id' => $this->userId,
            'date' => $this->date
        ]);

        try {
            $user = User::withTrashed()->findOrFail($this->userId);
            $date = Carbon::parse($this->date);
            $now = now();
            // Calculate new data
            $reportData = $this->calculateCoverageDataForUserAndDate($user, $date);

            if ($reportData !== null) {
                // Update or create the record
                CoverageReportData::updateOrCreateForDate(
                    $user->id,
                    $date->toDateString(),
                    $reportData
                );
                $this->updateSyncTimestamp($now);
            }

            $logger->info('Coverage report data updated successfully');

        } catch (\Exception $e) {
            $logger->error('Failed to update coverage report data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Calculate coverage data for a specific user and date
     */
    private function calculateCoverageDataForUserAndDate(User $user, Carbon $date): ?array
    {
        // Get visits for the date
        $visits = Visit::where('user_id', $user->id)
            ->whereDate('visit_date', $date)
            ->get();

        // If no visits for this date, return null to skip
        if ($visits->isEmpty()) {
            return null;
        }

        // Get activities for the date
        $activitiesCount = Activity::where('user_id', $user->id)
            ->whereDate('created_at', $date)
            ->count();

        // Get office work for the date
        $officeWorkCount = OfficeWork::where('user_id', $user->id)
            ->whereDate('created_at', $date)
            ->where('status', 'approved')
            ->count();

        // Calculate metrics
        $actualVisits = $visits->where('status', 'visited')->count();
        $totalVisits = $visits->count();

        $dailyVisitTarget = $user->dailyVisitTarget;
        $callRate = $totalVisits > 0 ? round(($actualVisits / $totalVisits) * $dailyVisitTarget, 2) : 0;

        // Calculate SOPs percentage
        $sops = $actualVisits > 0 ? min(($actualVisits / $dailyVisitTarget) * 100, 100) : 0;

        $vacationDuration = VacationDuration::select('start_shift','end_shift','start','end')
            ->join('vacation_requests', 'vacation_durations.vacation_request_id', '=', 'vacation_requests.id')
            ->where('vacation_requests.user_id', $user->id)
            ->where('vacation_requests.approved', '>', 0)
            ->where('vacation_durations.start', '<=', $date->format('Y-m-d'))
            ->where('vacation_durations.end', '>=', $date->format('Y-m-d'))
            ->first();

        $vacationDays = DateHelper::calculateVacationDays($vacationDuration, $date, $actualVisits);

        $isOffDay = DateHelper::isOffDay($date);

        $worked = $activitiesCount > 0 || $officeWorkCount > 0 || $actualVisits > 0;
        $isWorkingDay = DateHelper::isWorkingDay($date) || $worked;

        $actualWorkingDays = match(true) {
            $isWorkingDay => 1,
            $isOffDay => 0,
            default => 1
        };

        $actualWorkingDays = $actualWorkingDays - $vacationDays;

        if($actualWorkingDays < 0){
            $actualWorkingDays = 0;
        }

        return [
            'working_days' => $actualWorkingDays,
            'daily_visit_target' => $dailyVisitTarget,
            'office_work_count' => $officeWorkCount,
            'activities_count' => $activitiesCount,
            'sops' => round($sops, 2),
            'actual_visits' => $actualVisits,
            'call_rate' => round($callRate, 2),
            'total_visits' => $totalVisits,
            'is_final' => !$date->isToday() || $this->finalize,
            'metadata' => [
                'sync_date' => now()->toISOString(),
                'visits_breakdown' => [
                    'visited' => $visits->where('status', 'visited')->count(),
                    'pending' => $visits->where('status', 'pending')->count(),
                    'planned' => $visits->where('status', 'planned')->count(),
                    'missed' => $visits->where('status', 'missed')->count(),
                ]
            ]
        ];
    }
    private function updateSyncTimestamp(Carbon $now)
    {
        Setting::updateCoverageReportSyncTimestamp($now->timestamp);
    }
}
