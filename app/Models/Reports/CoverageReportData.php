<?php

namespace App\Models\Reports;

use App\Models\Scopes\GetMineScope;
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
     * Get aggregated coverage report query using Eloquent Builder
     * @param string $fromDate
     * @param string $toDate
     * @param array $filters
     * @return Builder
     */
    public static function getAggregatedQuery($fromDate, $toDate, $filters = []): Builder
    {
        $filterUserIds = $filters['user_id'] ?? [];
        $userIds = GetMineScope::getUserIds();

        if($filterUserIds){
            $userIds = array_intersect($userIds, $filterUserIds);
        }

        DB::statement("SET SESSION sql_mode=''");

        $query = static::query()
            ->select([
                'coverage_report_data.user_id as user_id',
                'users.id as id',
                'users.name as user_name',
                // DB::raw('GROUP_CONCAT(DISTINCT areas.name) as area_name'),
                DB::raw('SUM(coverage_report_data.working_days) as working_days'),
                DB::raw('ROUND(AVG(coverage_report_data.daily_visit_target), 2) as daily_visit_target'),
                DB::raw('SUM(coverage_report_data.office_work_count) as office_work_count'),
                DB::raw('SUM(coverage_report_data.activities_count) as activities_count'),
                DB::raw('SUM(coverage_report_data.actual_working_days) as actual_working_days'),
                DB::raw('SUM(coverage_report_data.actual_working_days) * ROUND(AVG(coverage_report_data.daily_visit_target), 2) as monthly_visit_target'),
                DB::raw('ROUND(AVG(coverage_report_data.sops), 2) as sops'),
                DB::raw('SUM(coverage_report_data.actual_visits) as actual_visits'),
                DB::raw('ROUND(AVG(coverage_report_data.call_rate), 2) as call_rate'),
                DB::raw('SUM(coverage_report_data.total_visits) as total_visits'),
            ])
            ->join('users', 'coverage_report_data.user_id', '=', 'users.id')
            // ->join('area_user', 'users.id', '=', 'area_user.user_id')
            // ->join('areas', 'area_user.area_id', '=', 'areas.id')
            ->whereIn('coverage_report_data.user_id', $userIds)
            ->whereBetween('coverage_report_data.report_date', [$fromDate, $toDate])
            // where not all zeros
            ->where(function ($query) {
                $query->where('coverage_report_data.actual_working_days', '>', 0)
                    ->orWhere('coverage_report_data.actual_visits', '>', 0);
            })
            // ->having('SUM(coverage_report_data.actual_working_days)'), '>', 0)
            ->having(DB::raw('SUM(coverage_report_data.actual_visits)'), '>', 0)
            ->groupBy(
                'coverage_report_data.user_id',
                // 'areas.id',
                // 'areas.name',
                'users.id'
            );

        // // Apply area filter
        // $area = $filters['area'] ?? null;
        // if ($area) {
        //     $query->whereIn('areas.id', $area);
        // }

        // Apply user filter
        $userId = $filters['user_id'] ?? null;
        if ($userId) {
            $query->whereIn('coverage_report_data.user_id', is_array($userId) ? $userId : [$userId]);
        }

        // Apply grade filter (if users table has grade column)
        $grade = $filters['grade'] ?? null;
        if ($grade) {
            $query->whereIn('users.grade', is_array($grade) ? $grade : [$grade]);
        }

        // Apply additional filters as needed
        $clientType = $filters['client_type'] ?? null;
        if ($clientType) {
            // Assuming there's a client_type column or relationship
            $query->whereIn('users.client_type', is_array($clientType) ? $clientType : [$clientType]);
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

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange(Builder $query, $fromDate, $toDate): Builder
    {
        return $query->whereBetween('report_date', [$fromDate, $toDate]);
    }

    /**
     * Scope for filtering by user
     */
    public function scopeForUser(Builder $query, $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for filtering by multiple users
     */
    public function scopeForUsers(Builder $query, array $userIds): Builder
    {
        return $query->whereIn('user_id', $userIds);
    }

    /**
     * Get performance metrics for a user in a date range
     */
    public static function getUserPerformanceMetrics($userId, $fromDate, $toDate): array
    {
        $data = static::query()
            ->select([
                DB::raw('SUM(working_days) as total_working_days'),
                DB::raw('SUM(actual_visits) as total_actual_visits'),
                DB::raw('SUM(total_visits) as total_visits'),
                DB::raw('AVG(call_rate) as avg_call_rate'),
                DB::raw('AVG(sops) as avg_sops'),
            ])
            ->where('user_id', $userId)
            ->whereBetween('report_date', [$fromDate, $toDate])
            ->first();

        return [
            'total_working_days' => $data->total_working_days ?? 0,
            'total_actual_visits' => $data->total_actual_visits ?? 0,
            'total_visits' => $data->total_visits ?? 0,
            'avg_call_rate' => round($data->avg_call_rate ?? 0, 2),
            'avg_sops' => round($data->avg_sops ?? 0, 2),
            'visit_efficiency' => $data->total_visits > 0
                ? round(($data->total_actual_visits / $data->total_visits) * 100, 2)
                : 0,
        ];
    }
}