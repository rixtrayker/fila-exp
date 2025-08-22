<?php

namespace App\Services;

use App\Models\ClientType;
use App\Models\Scopes\GetMineScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class SOPsAndCallRateService
{

    /**
     * Build the SOPs and call rate query using optimized stored procedure for better performance
     */
    public function buildQuery(string $fromDate, string $toDate, array $filters = []): Builder
    {
        $userIds = $this->getFilteredUserIds($filters);
        $clientTypeId = $this->getClientTypeId($filters);

        // Use the optimized stored procedure
        $results = $this->getDataFromStoredProcedure($fromDate, $toDate, $userIds, $clientTypeId);
        
        // Convert results to a format that can be used with fromSub
        $sql = $this->buildResultsAsSql($results);

        return \App\Models\SOPsAndCallRate::query()
            ->fromSub($sql, 'sops_and_call_rate');
    }

    /**
     * Get filtered user IDs based on permissions and filters
     */
    protected function getFilteredUserIds(array $filters): array
    {
        $userIds = GetMineScope::getUserIds();

        if (empty($userIds)) {
            $userIds = DB::table('users')->pluck('id')->toArray();
        }

        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $userIds = array_intersect($userIds, $filters['user_id']);
        }

        return $userIds;
    }

    /**
     * Get client type ID from filters
     */
    protected function getClientTypeId(array $filters): int
    {
        $clientTypeId = $filters['client_type_id'] ?? ClientType::PM;

        if (is_array($clientTypeId)) {
            $clientTypeId = reset($clientTypeId) ?: ClientType::PM;
        }

        return (int) $clientTypeId;
    }

    /**
     * Get data from the optimized stored procedure
     */
    protected function getDataFromStoredProcedure(string $fromDate, string $toDate, array $userIds, int $clientTypeId): array
    {
        if (empty($userIds)) {
            return [];
        }

        $userIdsStr = implode(',', $userIds);
        
        // Call the optimized stored procedure
        return DB::select("CALL GetSOPsAndCallRateData(?, ?, ?, ?)", [
            $fromDate,
            $toDate, 
            $userIdsStr,
            $clientTypeId > 0 ? $clientTypeId : null
        ]);
    }

    /**
     * Convert procedure results to SQL that can be used with fromSub
     */
    protected function buildResultsAsSql(array $results): string
    {
        if (empty($results)) {
            return "SELECT 1 WHERE FALSE";
        }

        $rows = [];
        foreach ($results as $result) {
            $row = [
                'id' => (int) $result->id,
                'name' => "'" . addslashes($result->name) . "'",
                'area_name' => "'" . addslashes($result->area_name) . "'",
                'working_days' => (int) $result->working_days,
                'daily_visit_target' => (int) $result->daily_visit_target,
                'monthly_visit_target' => (int) $result->monthly_visit_target,
                'office_work_count' => (int) $result->office_work_count,
                'activities_count' => (int) $result->activities_count,
                'actual_working_days' => (int) $result->actual_working_days,
                'actual_visits' => (int) $result->actual_visits,
                'call_rate' => (float) $result->call_rate,
                'sops' => (float) $result->sops,
                'total_visits' => (int) $result->total_visits,
                'vacation_days' => (float) $result->vacation_days
            ];
            
            $rows[] = 'SELECT ' . implode(', ', array_map(function($key, $value) {
                return "{$value} as {$key}";
            }, array_keys($row), array_values($row)));
        }

        return implode(' UNION ALL ', $rows);
    }

    /**
     * Get user coverage report data directly from database
     */
    public function getUserData(int $userId, string $fromDate, string $toDate, int $clientTypeId): array
    {
        return $this->getUserDataFromDatabase($userId, $fromDate, $toDate, $clientTypeId);
    }

    /**
     * Get user data directly from database using optimized stored procedure
     */
    protected function getUserDataFromDatabase(int $userId, string $fromDate, string $toDate, int $clientTypeId): array
    {
        $results = $this->getDataFromStoredProcedure($fromDate, $toDate, [$userId], $clientTypeId);
        
        if (empty($results)) {
            return [];
        }

        $result = $results[0];

        return [
            'working_days' => (int) $result->working_days,
            'daily_visit_target' => (int) $result->daily_visit_target,
            'monthly_visit_target' => (int) $result->monthly_visit_target,
            'office_work_count' => (int) $result->office_work_count,
            'activities_count' => (int) $result->activities_count,
            'actual_working_days' => (int) $result->actual_working_days,
            'sops' => (float) $result->sops,
            'actual_visits' => (int) $result->actual_visits,
            'call_rate' => (float) $result->call_rate,
            'total_visits' => (int) $result->total_visits,
            'vacation_days' => (float) $result->vacation_days,
        ];
    }

    /**
     * Get working days count for a date range
     */
    public function getWorkingDays(string $fromDate, string $toDate): int
    {
        $result = DB::selectOne("SELECT GetWorkingDaysCount(?, ?) as days", [$fromDate, $toDate]);
        return $result->days ?? 0;
    }
}
