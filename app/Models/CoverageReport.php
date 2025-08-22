<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Added Log facade

class CoverageReport extends Model
{
    // This model uses stored procedures for data access, not a table
    public $timestamps = false;
    public $incrementing = false;

    protected $table = 'coverage_reports'; // Add table name for compatibility
    protected $primaryKey = 'id'; // Define primary key

    protected $fillable = [
        'id',
        'name',
        'area_name',
        'working_days',
        'daily_visit_target',
        'monthly_visit_target',
        'office_work_count',
        'activities_count',
        'actual_working_days',
        'sops',
        'actual_visits',
        'call_rate',
        'total_visits',
        'vacation_days'
    ];

    protected $casts = [
        'id' => 'integer',
        'working_days' => 'integer',
        'daily_visit_target' => 'integer',
        'monthly_visit_target' => 'integer',
        'office_work_count' => 'integer',
        'activities_count' => 'integer',
        'actual_working_days' => 'integer',
        'sops' => 'float',
        'actual_visits' => 'integer',
        'call_rate' => 'float',
        'total_visits' => 'integer',
        'vacation_days' => 'float'
    ];

    /**
     * Get coverage report data from URL parameters or request
     */
    public static function getReportDataFromUrl(): array
    {
        $filters = static::extractFiltersFromUrl();

        return static::getReportDataWithFilters($filters);
    }

    /**
     * Get coverage report data with filters applied from any source
     */
    public static function getReportDataWithFilters(array $appliedFilters): array
    {
        $filters = static::normalizeFilters($appliedFilters);

        return static::getReportData(
            $filters['from_date'],
            $filters['to_date'],
            $filters
        );
    }

    /**
     * Get coverage report data using the stored procedure with filters
     */
    public static function getReportData(string $fromDate, string $toDate, array $filters = []): array
    {
        $userIds = static::getFilteredUserIds($filters);
        $clientTypeId = static::getClientTypeId($filters);

        // Format user IDs as comma-separated string for MySQL procedure
        $userIdsParam = !empty($userIds) ? implode(',', $userIds) : '';

        // Use 0 for client type to mean "all types" instead of null
        $clientTypeParam = $clientTypeId ?: 0;

        // Log the parameters being sent to stored procedure
        Log::info('CoverageReport - Calling stored procedure GetCoverageReportData with params:', [
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'userIds' => $userIds,
            'userIdsParam' => $userIdsParam,
            'clientTypeId' => $clientTypeId,
            'clientTypeParam' => $clientTypeParam,
            'totalUsers' => count($userIds)
        ]);

        // Call the stored procedure with parameters
        $results = DB::select('CALL GetCoverageReportData(?, ?, ?, ?)', [
            $fromDate,
            $toDate,
            $userIdsParam,
            $clientTypeParam
        ]);

        // Log the results
        Log::info('CoverageReport - Stored procedure GetCoverageReportData returned:', [
            'totalResults' => count($results),
            'firstResult' => $results[0] ?? null,
            'sampleResults' => array_slice($results, 0, 3), // First 3 results for debugging
            'allUserIdsInResults' => collect($results)->pluck('id')->toArray()
        ]);

        return $results;
    }

    /**
     * Get report data with default current month range
     */
    public static function getCurrentMonthReportData(array $filters = []): array
    {
        $fromDate = today()->startOfMonth()->toDateString();
        $toDate = today()->toDateString();

        return static::getReportData($fromDate, $toDate, $filters);
    }

    /**
     * Get filtered user IDs based on permissions and filters
     */
    protected static function getFilteredUserIds(array $filters): array
    {
        $userIds = GetMineScope::getUserIds();

        Log::info('CoverageReport - GetMineScope returned user IDs:', [
            'userIds' => $userIds,
            'count' => count($userIds),
            'currentUserId' => auth()->id()
        ]);

        if (empty($userIds)) {
            $userIds = DB::table('users')->pluck('id')->toArray();
            Log::warning('CoverageReport - No users from GetMineScope, falling back to all users:', [
                'fallbackUserIds' => $userIds,
                'fallbackCount' => count($userIds)
            ]);
        }

        if (isset($filters['user_id']) && !empty($filters['user_id'])) {
            $originalUserIds = $userIds;
            $userIds = array_intersect($userIds, $filters['user_id']);
            Log::info('CoverageReport - Applied user filter:', [
                'originalUserIds' => $originalUserIds,
                'filterUserIds' => $filters['user_id'],
                'filteredUserIds' => $userIds,
                'filteredCount' => count($userIds)
            ]);
        }

        Log::info('CoverageReport - Final user IDs for stored procedure:', [
            'finalUserIds' => $userIds,
            'finalCount' => count($userIds)
        ]);

        return $userIds;
    }

    /**
     * Get client type ID from filters
     */
    protected static function getClientTypeId(array $filters): int
    {
        $clientTypeId = $filters['client_type_id'] ?? ClientType::PM;

        if (is_array($clientTypeId)) {
            $clientTypeId = reset($clientTypeId) ?: ClientType::PM;
        }

        Log::info('CoverageReport - Client type determined:', [
            'originalClientTypeId' => $filters['client_type_id'] ?? 'not_set',
            'finalClientTypeId' => $clientTypeId,
            'ClientType::PM' => ClientType::PM
        ]);

        return (int) $clientTypeId;
    }

    /**
     * Get report data for Filament Resource (returns collection-like structure)
     */
    public static function getForFilamentResource(array $filters = []): \Illuminate\Support\Collection
    {
        $data = static::getReportDataWithFilters($filters);
        return collect($data);
    }

    /**
     * Convert procedure result to collection for easier manipulation
     */
    public static function toCollection(array $procedureResults): \Illuminate\Support\Collection
    {
        return collect($procedureResults)->map(function ($item) {
            return (object) $item;
        });
    }

    /**
     * Extract and normalize filters from URL/request parameters
     */
    protected static function extractFiltersFromUrl(): array
    {
        $request = request();

        // Handle both direct request parameters and tableFilters structure
        $tableFilters = $request->input('tableFilters', []);

        // Extract date range
        $dateRange = $tableFilters['date_range'] ?? $request->only(['from_date', 'to_date']);
        $fromDate = $dateRange['from_date'] ?? $request->input('from_date', today()->startOfMonth()->toDateString());
        $toDate = $dateRange['to_date'] ?? $request->input('to_date', today()->toDateString());

        // Extract user filter
        $userFilter = $tableFilters['user_id'] ?? $request->input('user_id');
        if (is_array($userFilter) && array_key_exists('values', $userFilter)) {
            $userFilter = $userFilter['values'];
        }

        // Extract client type filter
        $clientTypeFilter = $tableFilters['client_type_id'] ?? $request->input('client_type_id');
        if (is_array($clientTypeFilter) && array_key_exists('values', $clientTypeFilter)) {
            $clientTypeFilter = $clientTypeFilter['values'];
        }

        return [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'user_id' => $userFilter,
            'client_type_id' => $clientTypeFilter ?? ClientType::PM
        ];
    }

    /**
     * Normalize filters from any source (Resource, Form, API, etc.)
     */
    protected static function normalizeFilters(array $appliedFilters): array
    {
        // Handle different filter structures
        $normalized = [];

        // Date range handling
        if (isset($appliedFilters['date_range'])) {
            $dateRange = $appliedFilters['date_range'];
            $normalized['from_date'] = $dateRange['from_date'] ?? today()->startOfMonth()->toDateString();
            $normalized['to_date'] = $dateRange['to_date'] ?? today()->toDateString();
        } else {
            $normalized['from_date'] = $appliedFilters['from_date'] ?? today()->startOfMonth()->toDateString();
            $normalized['to_date'] = $appliedFilters['to_date'] ?? today()->toDateString();
        }

        // User filter normalization
        $userFilter = $appliedFilters['user_id'] ?? null;
        if (is_array($userFilter)) {
            // Handle different array structures
            if (array_key_exists('values', $userFilter)) {
                $normalized['user_id'] = $userFilter['values'];
            } elseif (array_key_exists('value', $userFilter)) {
                $normalized['user_id'] = is_array($userFilter['value']) ? $userFilter['value'] : [$userFilter['value']];
            } else {
                $normalized['user_id'] = $userFilter;
            }
        } else {
            $normalized['user_id'] = $userFilter ? [$userFilter] : null;
        }

        // Client type filter normalization
        $clientTypeFilter = $appliedFilters['client_type_id'] ?? ClientType::PM;
        if (is_array($clientTypeFilter)) {
            if (array_key_exists('values', $clientTypeFilter)) {
                $normalized['client_type_id'] = is_array($clientTypeFilter['values'])
                    ? reset($clientTypeFilter['values'])
                    : $clientTypeFilter['values'];
            } elseif (array_key_exists('value', $clientTypeFilter)) {
                $normalized['client_type_id'] = $clientTypeFilter['value'];
            } else {
                $normalized['client_type_id'] = is_array($clientTypeFilter) ? reset($clientTypeFilter) : $clientTypeFilter;
            }
        } else {
            $normalized['client_type_id'] = $clientTypeFilter;
        }

        return $normalized;
    }

    /**
     * Get count of total records for pagination (calls procedure and counts results)
     */
    public static function getReportCount(array $filters = []): int
    {
        $data = static::getReportDataWithFilters($filters);
        return count($data);
    }

    /**
     * Get report data with custom date range and flexible filters
     */
    public static function getCustomReportData(
        ?string $fromDate = null,
        ?string $toDate = null,
        ?array $userIds = null,
        ?int $clientTypeId = null,
        array $additionalFilters = []
    ): array {
        $filters = array_merge([
            'from_date' => $fromDate ?? today()->startOfMonth()->toDateString(),
            'to_date' => $toDate ?? today()->toDateString(),
            'user_id' => $userIds,
            'client_type_id' => $clientTypeId ?? ClientType::PM
        ], $additionalFilters);

        return static::getReportData(
            $filters['from_date'],
            $filters['to_date'],
            $filters
        );
    }
}
