<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class CoverageReportData extends Model
{
    use HasFactory;

    protected $table = 'coverage_report_data';

    protected $fillable = [
        'user_id',
        'report_date',
        'working_days',
        'daily_visit_target',
        'office_work_count',
        'activities_count',
        'actual_working_days',
        'monthly_visit_target',
        'sops',
        'actual_visits',
        'call_rate',
        'total_visits',
        'metadata',
    ];

    protected $casts = [
        'report_date' => 'date',
        'sops' => 'decimal:2',
        'call_rate' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get aggregated coverage report query
     * @param string $fromDate
     * @param string $toDate
     * @param array $filters
     * @return Builder
     */
    public static function getAggregatedQuery($fromDate, $toDate, $filters = []): Builder
    {
        DB::statement("SET SESSION sql_mode=''");
        // TODO: add grade filter, client type filter, brick filter, visit status filter
        // TODO: system users can see data of users under their management

        $query = static::query()
            ->select([
                'user_id',
                DB::raw('areas.name as area_name'),
                DB::raw('SUM(working_days) as working_days'),
                DB::raw('ROUND(AVG(daily_visit_target), 2) as daily_visit_target'),
                DB::raw('SUM(office_work_count) as office_work_count'),
                DB::raw('SUM(activities_count) as activities_count'),
                DB::raw('SUM(actual_working_days) as actual_working_days'),
                DB::raw('SUM(monthly_visit_target) as monthly_visit_target'),
                DB::raw('ROUND(AVG(sops), 2) as sops'),
                DB::raw('SUM(actual_visits) as actual_visits'),
                DB::raw('ROUND(AVG(call_rate), 2) as call_rate'),
                DB::raw('SUM(total_visits) as total_visits'),
            ])
            ->join('users', 'coverage_report_data.user_id', '=', 'users.id')
            ->join('areas', 'users.area_id', '=', 'areas.id')
            ->whereBetween('report_date', [$fromDate, $toDate])
            ->groupBy('user_id', 'areas.id')
            ->with('user');

        $area = $filters['area'] ?? null;
        if ($area) {
            $query->whereIn('areas.id', $area);
        }

        return $query;
    }

    public static function getAggregatedData($fromDate, $toDate, $filters = []): Collection
    {
        return self::getAggregatedQuery($fromDate, $toDate, $filters)->get();
    }

    /**
     * Get data for a specific user and date range
     */
    public static function getUserData($userId, $fromDate, $toDate)
    {
        return static::where('user_id', $userId)
            ->whereBetween('report_date', [$fromDate, $toDate])
            ->orderBy('report_date')
            ->get();
    }

    /**
     * Create or update report data for a specific date
     */
    public static function updateOrCreateForDate($userId, $date, $data)
    {
        return static::updateOrCreate(
            [
                'user_id' => $userId,
                'report_date' => $date,
            ],
            $data
        );
    }

    /**
     * Clean old data (keep only last 30 days)
     */
    public static function cleanOldData()
    {
        $cutoffDate = Carbon::now()->subDays(30);
        return static::where('report_date', '<', $cutoffDate)->delete();
    }
}
