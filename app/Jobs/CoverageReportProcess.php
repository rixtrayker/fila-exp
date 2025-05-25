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
    public $queue = 'reports';

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

            // Calculate new data
            $reportData = $this->calculateCoverageDataForUserAndDate($user, $date);

            if ($reportData !== null) {
                // Update or create the record
                CoverageReportData::updateOrCreateForDate(
                    $user->id,
                    $date->toDateString(),
                    $reportData
                );
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
        $callRate = $totalVisits > 0 ? ($actualVisits / $totalVisits) * 100 : 0;

        // Get daily targets of medical rep or district manager or its role
        $dailyVisitTarget = $user->dailyVisitTarget;
        $monthlyVisitTarget = $dailyVisitTarget * 22; // Assuming 22 working days

        // Calculate SOPs percentage
        $sops = $actualVisits > 0 ? min(($actualVisits / $dailyVisitTarget) * 100, 100) : 0;

        $vacationDuration = VacationDuration::with('vacationRequest')
            ->whereHas('vacationRequest', function ($query) use ($user) {
                $query->where('user_id', $user->id);
                $query->where('approved', '>', 0);
            })
            ->where('start', '<=', $date->format('Y-m-d'))
            ->where('end', '>=', $date->format('Y-m-d'))
            ->first();

        // Calculate vacation days
        $vacationDays = 0;
        if ($vacationDuration) {
            if ($vacationDuration->start_shift == 'PM' && $vacationDuration->start == $date->format('Y-m-d')) {
                $vacationDays = 0.5;
            } else if ($vacationDuration->end_shift == 'AM' && $vacationDuration->end == $date->format('Y-m-d')) {
                $vacationDays = 0.5;
            } else {
                $vacationDays = 1;
            }
            // If he has done visits on this date then it's 0.5
            if ($vacationDays === 0 && $actualVisits > 0) {
                $vacationDays = 0.5;
            }
        }

        $isWorkingDay = DateHelper::isWorkingDay($date) && $actualVisits > 0;
        $hasActivity = $activitiesCount > 0;
        $hasOfficeWork = $officeWorkCount > 0;

        $workingDays = $isWorkingDay ? 1 : 0;
        $actualWorkingDays = $workingDays - $vacationDays;

        return [
            'working_days' => $workingDays,
            'daily_visit_target' => $dailyVisitTarget,
            'office_work_count' => $officeWorkCount,
            'activities_count' => $activitiesCount,
            'actual_working_days' => $actualWorkingDays,
            'monthly_visit_target' => $monthlyVisitTarget,
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
}
