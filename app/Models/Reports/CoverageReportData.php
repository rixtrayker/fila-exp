<?php

namespace App\Models\Reports;

use App\Models\Scopes\GetMineScope;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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
     * Normalize filter values to handle nested arrays from Filament
     */
        private static function normalizeFilterValues($value)
    {
        if (is_array($value) && isset($value['values'])) {
            return $value['values'];
        }
        return $value;
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

        // First, get the filtered user IDs based on the filters
        // $filteredUserIds = self::getFilteredUserIds($fromDate, $toDate, $filters, $userIds);



        // // If no filtered user IDs, return empty query
        // if (empty($filteredUserIds)) {
        //     return static::query()->whereRaw('1 = 0'); // Return empty result set
        // }

        // Build the SQL query manually to avoid any interference
        // $userIdsString = implode(',', $filteredUserIds);

        $sql = "
            SELECT
                coverage_report_data.user_id as user_id,
                users.id as id,
                GROUP_CONCAT(DISTINCT users.name) as name,
                GROUP_CONCAT(DISTINCT areas.name) as area_name,
                SUM(coverage_report_data.working_days) as working_days,
                ROUND(AVG(coverage_report_data.daily_visit_target), 2) as daily_visit_target,
                SUM(coverage_report_data.office_work_count) as office_work_count,
                SUM(coverage_report_data.activities_count) as activities_count,
                SUM(coverage_report_data.actual_working_days) as actual_working_days,
                SUM(coverage_report_data.actual_working_days) * ROUND(AVG(coverage_report_data.daily_visit_target), 2) as monthly_visit_target,
                ROUND(AVG(coverage_report_data.sops), 2) as sops,
                SUM(coverage_report_data.actual_visits) as actual_visits,
                ROUND(AVG(coverage_report_data.call_rate), 2) as call_rate,
                SUM(coverage_report_data.total_visits) as total_visits
            FROM coverage_report_data
            INNER JOIN users ON coverage_report_data.user_id = users.id
            LEFT JOIN area_user ON users.id = area_user.user_id
            LEFT JOIN areas ON area_user.area_id = areas.id
            AND coverage_report_data.report_date BETWEEN '{$fromDate}' AND '{$toDate}'
            AND (coverage_report_data.actual_working_days > 0 OR coverage_report_data.actual_visits > 0)
            GROUP BY coverage_report_data.user_id, users.id
            HAVING SUM(coverage_report_data.actual_visits) > 0
        ";
        // $sql = "
        //     SELECT
        //         coverage_report_data.user_id as user_id,
        //         users.id as id,
        //         GROUP_CONCAT(DISTINCT users.name) as name,
        //         GROUP_CONCAT(DISTINCT areas.name) as area_name,
        //         SUM(coverage_report_data.working_days) as working_days,
        //         ROUND(AVG(coverage_report_data.daily_visit_target), 2) as daily_visit_target,
        //         SUM(coverage_report_data.office_work_count) as office_work_count,
        //         SUM(coverage_report_data.activities_count) as activities_count,
        //         SUM(coverage_report_data.actual_working_days) as actual_working_days,
        //         SUM(coverage_report_data.actual_working_days) * ROUND(AVG(coverage_report_data.daily_visit_target), 2) as monthly_visit_target,
        //         ROUND(AVG(coverage_report_data.sops), 2) as sops,
        //         SUM(coverage_report_data.actual_visits) as actual_visits,
        //         ROUND(AVG(coverage_report_data.call_rate), 2) as call_rate,
        //         SUM(coverage_report_data.total_visits) as total_visits
        //     FROM coverage_report_data
        //     INNER JOIN users ON coverage_report_data.user_id = users.id
        //     LEFT JOIN area_user ON users.id = area_user.user_id
        //     LEFT JOIN areas ON area_user.area_id = areas.id
        //     WHERE coverage_report_data.user_id IN ({$userIdsString})
        //     AND coverage_report_data.report_date BETWEEN '{$fromDate}' AND '{$toDate}'
        //     AND (coverage_report_data.actual_working_days > 0 OR coverage_report_data.actual_visits > 0)
        //     GROUP BY coverage_report_data.user_id, users.id
        //     HAVING SUM(coverage_report_data.actual_visits) > 0
        // ";



        // Return a query that uses the raw SQL
        return static::query()->from(DB::raw("({$sql}) as coverage_report_data"));
    }

    /**
     * Get filtered user IDs based on the applied filters
     * @param string $fromDate
     * @param string $toDate
     * @param array $filters
     * @param array $baseUserIds
     * @return array
     */
    private static function getFilteredUserIds($fromDate, $toDate, $filters, $baseUserIds): array
    {
        $userIds = $baseUserIds;

        // If no base user IDs, return empty array
        if (empty($userIds)) {
            return [];
        }

        try {
                        // Apply area filter
            $area = self::normalizeFilterValues($filters['area'] ?? null);
            if ($area && !empty($area)) {
                $areaUserIds = DB::table('users')
                    ->join('area_user', 'users.id', '=', 'area_user.user_id')
                    ->whereIn('area_user.area_id', is_array($area) ? $area : [$area])
                    ->pluck('users.id')
                    ->toArray();
                $userIds = array_intersect($userIds, $areaUserIds);
            }

            // Apply user filter
            $userId = self::normalizeFilterValues($filters['user_id'] ?? null);
            if ($userId && !empty($userId)) {
                $userIds = array_intersect($userIds, is_array($userId) ? $userId : [$userId]);
            }

            // Apply grade filter (filter users based on client grades they have visited)
            $grade = self::normalizeFilterValues($filters['grade'] ?? null);
            if ($grade && !empty($grade) && !empty($userIds)) {
                $gradeUserIds = DB::table('visits')
                    ->join('clients', 'visits.client_id', '=', 'clients.id')
                    ->whereIn('visits.user_id', $userIds)
                    ->whereBetween('visits.visit_date', [$fromDate, $toDate])
                    ->whereIn('clients.grade', is_array($grade) ? $grade : [$grade])
                    ->distinct()
                    ->pluck('visits.user_id')
                    ->toArray();
                $userIds = array_intersect($userIds, $gradeUserIds);
            }

            // Apply client_type_id filter (filter users based on client types they have visited)
            $clientTypeId = self::normalizeFilterValues($filters['client_type_id'] ?? null);
            if ($clientTypeId && !empty($clientTypeId) && !empty($userIds)) {
                $clientTypeUserIds = DB::table('visits')
                    ->join('clients', 'visits.client_id', '=', 'clients.id')
                    ->whereIn('visits.user_id', $userIds)
                    ->whereBetween('visits.visit_date', [$fromDate, $toDate])
                    ->whereIn('clients.client_type_id', is_array($clientTypeId) ? $clientTypeId : [$clientTypeId])
                    ->distinct()
                    ->pluck('visits.user_id')
                    ->toArray();
                $userIds = array_intersect($userIds, $clientTypeUserIds);
            }

            return $userIds;
        } catch (\Exception $e) {
            // Log the error and return base user IDs as fallback
            Log::error('Error in getFilteredUserIds: ' . $e->getMessage(), [
                'filters' => $filters,
                'fromDate' => $fromDate,
                'toDate' => $toDate,
                'baseUserIds' => $baseUserIds
            ]);
            return $baseUserIds;
        }
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
