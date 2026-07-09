<?php

namespace App\Models;

use App\Models\Scopes\GetMineScope;
use App\Models\User;
use App\Models\ClientType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

use function PHPUnit\Framework\isInstanceOf;

class AccountsCoverageReport extends Model
{
    // This model uses a query builder for data access, not a table
    public $timestamps = false;
    public $incrementing = false;

    protected $table = 'accounts_coverage_reports'; // Add table name for compatibility
    protected $primaryKey = 'id'; // Define primary key

    protected $fillable = [
        'id',
        'medical_rep_name',
        'total_area_clients',
        'visited_doctors',
        'unvisited_doctors',
        'coverage_percentage',
        'actual_visits',
        'clinic_visits',
        'planned_visits',
        'random_visits',
        'planned_random_ratio',
    ];

    protected $appends = [
        'client_breakdown_all_url',
        'client_breakdown_visited_url',
        'client_breakdown_unvisited_url',
        'visit_breakdown_url',
        'clinic_visit_breakdown_url',
    ];

    protected $casts = [
        'id' => 'integer',
        'total_area_clients' => 'integer',
        'visited_doctors' => 'integer',
        'unvisited_doctors' => 'integer',
        'coverage_percentage' => 'float',
        'actual_visits' => 'integer',
        'clinic_visits' => 'integer',
        'planned_visits' => 'integer',
        'random_visits' => 'integer',
        'planned_random_ratio' => 'float',
    ];

    /**
     * SQL for the per-user accountable client pool (user_id, client_id rows).
     *
     * Users WITH a personal client list (client_user rows) are evaluated
     * against that list; users WITHOUT one keep the legacy area-derived pool
     * from user_bricks_view. Both branches respect the active flag and the
     * client type filter. See Client::scopeAccountablePool for the same rule
     * on the Eloquent side.
     */
    private static function buildAccountableClientsPoolSql(int $clientTypeId, string $userIdsStr): string
    {
        return "
                    SELECT
                        cu.user_id,
                        cu.client_id
                    FROM client_user cu
                    JOIN clients c ON cu.client_id = c.id
                    WHERE c.active = 1
                      AND c.client_type_id = {$clientTypeId}
                      AND cu.user_id IN ({$userIdsStr})
                    UNION ALL
                    SELECT
                        ubv.user_id,
                        c.id as client_id
                    FROM user_bricks_view ubv
                    JOIN clients c ON ubv.brick_id = c.brick_id
                    WHERE c.active = 1
                      AND c.client_type_id = {$clientTypeId}
                      AND ubv.user_id IN ({$userIdsStr})
                      AND NOT EXISTS (SELECT 1 FROM client_user cux WHERE cux.user_id = ubv.user_id)
        ";
    }

    /**
     * SQL for visits restricted to each user's accountable client pool
     * (personal list when present, legacy area-derived pool otherwise).
     *
     * @param string $select        Columns to select from the visits alias `v`
     * @param string $extraCondition Additional WHERE fragment (e.g. plan filter)
     */
    private static function buildAccountableVisitsPoolSql(
        string $select,
        string $extraCondition,
        string $fromDate,
        string $toDate,
        int $clientTypeId,
        string $userIdsStr
    ): string {
        return "
                    SELECT {$select}
                    FROM visits v
                    JOIN clients c ON v.client_id = c.id
                    JOIN client_user cu ON cu.client_id = c.id AND cu.user_id = v.user_id
                    WHERE v.status = 'visited'
                      AND DATE(v.visit_date) BETWEEN '{$fromDate}' AND '{$toDate}'
                      AND v.deleted_at IS NULL
                      AND c.active = 1
                      AND c.client_type_id = {$clientTypeId}
                      {$extraCondition}
                      AND cu.user_id IN ({$userIdsStr})
                    UNION ALL
                    SELECT {$select}
                    FROM visits v
                    JOIN clients c ON v.client_id = c.id
                    JOIN user_bricks_view ubv ON c.brick_id = ubv.brick_id AND v.user_id = ubv.user_id
                    WHERE v.status = 'visited'
                      AND DATE(v.visit_date) BETWEEN '{$fromDate}' AND '{$toDate}'
                      AND v.deleted_at IS NULL
                      AND c.active = 1
                      AND c.client_type_id = {$clientTypeId}
                      {$extraCondition}
                      AND ubv.user_id IN ({$userIdsStr})
                      AND NOT EXISTS (SELECT 1 FROM client_user cux WHERE cux.user_id = v.user_id)
        ";
    }

    /**
     * Build the accounts coverage report query using the user_bricks_view
     * This view consolidates user brick access through direct assignments and area-based access.
     *
     * Users who maintain a personal client list (client_user rows) have their
     * denominators and coverage counts computed over that list instead of the
     * area-derived pool; users without one keep the legacy behavior.
     */
    public static function buildReportQuery(string $fromDate, string $toDate, ?array $medicalRepIds = null, ?int $clientTypeId = null): Builder
    {
        $userIds = GetMineScope::getUserIds();

        if (empty($userIds)) {
            return User::query()->whereRaw('1 = 0');
        }

        // If specific medical reps are selected, filter by them
        if (!empty($medicalRepIds)) {
            $userIds = array_intersect($userIds, $medicalRepIds);
            if (empty($userIds)) {
                return User::query()->whereRaw('1 = 0');
            }
        }

        $userIdsStr = implode(',', $userIds);

        // Set default client type if not provided
        if ($clientTypeId === null) {
            $clientTypeId = ClientType::PM;
        }

        return User::withoutGlobalScopes()
            ->select([
                'users.id',
                'users.name as medical_rep_name',
                DB::raw('COALESCE(area_clients.total_clients, 0) as total_area_clients'),
                DB::raw('COALESCE(visited_clients.visited_count, 0) as visited_doctors'),
                DB::raw('COALESCE(area_clients.total_clients, 0) - COALESCE(visited_clients.visited_count, 0) as unvisited_doctors'),
                DB::raw("
                    CASE
                        WHEN COALESCE(area_clients.total_clients, 0) > 0 THEN
                            ROUND((COALESCE(visited_clients.visited_count, 0) * 100.0) / area_clients.total_clients, 2)
                        ELSE 0
                    END as coverage_percentage
                "),
                DB::raw('COALESCE(actual_visits.visit_count, 0) as actual_visits'),
                DB::raw('COALESCE(clinic_visits.clinic_visit_count, 0) as clinic_visits'),
                DB::raw('COALESCE(planned_visits.planned_visit_count, 0) as planned_visits'),
                DB::raw('COALESCE(random_visits.random_visit_count, 0) as random_visits'),
                DB::raw("
                    CASE
                        WHEN COALESCE(random_visits.random_visit_count, 0) > 0 THEN
                            ROUND((COALESCE(planned_visits.planned_visit_count, 0) * 1.0) / random_visits.random_visit_count, 2)
                        ELSE 0
                    END as planned_random_ratio
                ")
            ])
            ->leftJoin(DB::raw("(
                SELECT
                    pool.user_id,
                    COUNT(DISTINCT pool.client_id) as total_clients
                FROM (" . self::buildAccountableClientsPoolSql($clientTypeId, $userIdsStr) . ") as pool
                GROUP BY pool.user_id
            ) as area_clients"), 'users.id', '=', 'area_clients.user_id')
            ->leftJoin(DB::raw("(
                SELECT
                    pool.user_id,
                    COUNT(DISTINCT pool.client_id) as visited_count
                FROM (" . self::buildAccountableVisitsPoolSql('v.user_id, v.client_id', '', $fromDate, $toDate, $clientTypeId, $userIdsStr) . ") as pool
                GROUP BY pool.user_id
            ) as visited_clients"), 'users.id', '=', 'visited_clients.user_id')
            ->leftJoin(DB::raw("(
                SELECT
                    pool.user_id,
                    COUNT(*) as visit_count
                FROM (" . self::buildAccountableVisitsPoolSql('v.user_id', '', $fromDate, $toDate, $clientTypeId, $userIdsStr) . ") as pool
                GROUP BY pool.user_id
            ) as actual_visits"), 'users.id', '=', 'actual_visits.user_id')
            ->leftJoin(DB::raw("(
                SELECT
                    pool.user_id,
                    COUNT(*) as clinic_visit_count
                FROM (" . self::buildAccountableVisitsPoolSql('v.user_id', '', $fromDate, $toDate, $clientTypeId, $userIdsStr) . ") as pool
                GROUP BY pool.user_id
            ) as clinic_visits"), 'users.id', '=', 'clinic_visits.user_id')
            ->leftJoin(DB::raw("(
                SELECT
                    pool.user_id,
                    COUNT(*) as planned_visit_count
                FROM (" . self::buildAccountableVisitsPoolSql('v.user_id', 'AND v.plan_id IS NOT NULL', $fromDate, $toDate, $clientTypeId, $userIdsStr) . ") as pool
                GROUP BY pool.user_id
            ) as planned_visits"), 'users.id', '=', 'planned_visits.user_id')
            ->leftJoin(DB::raw("(
                SELECT
                    pool.user_id,
                    COUNT(*) as random_visit_count
                FROM (" . self::buildAccountableVisitsPoolSql('v.user_id', 'AND v.plan_id IS NULL', $fromDate, $toDate, $clientTypeId, $userIdsStr) . ") as pool
                GROUP BY pool.user_id
            ) as random_visits"), 'users.id', '=', 'random_visits.user_id')
            ->where('users.is_active', 1)
            ->whereIn('users.id', $userIds)
            ->orderBy('coverage_percentage', 'desc')
            ->orderBy('medical_rep_name', 'asc');
    }

    /**
     * Get report data for the Report class (returns Collection)
     */
    public static function getReportData(?array $filters = []): Collection
    {
        $fromDate = $filters['from_date'] ?? today()->firstOfMonth()->toDateString();
        $toDate = $filters['to_date'] ?? today()->toDateString();
        $medicalRepIds = $filters['medical_rep_id'] ?? null;
        $clientTypeId = $filters['client_type_id'] ?? ClientType::PM;

        $query = self::buildReportQuery($fromDate, $toDate, $medicalRepIds, $clientTypeId);
        $results = $query->get();

        $newResults = collect();
        foreach ($results as $result) {
            $newResult = [
                'id' => $result->id,
                'medical_rep_name' => $result->medical_rep_name,
                'total_area_clients' => $result->total_area_clients,
                'visited_doctors' => $result->visited_doctors,
                'unvisited_doctors' => $result->unvisited_doctors,
                'coverage_percentage' => $result->coverage_percentage,
                'actual_visits' => $result->actual_visits,
                'clinic_visits' => $result->clinic_visits,
                'planned_visits' => $result->planned_visits,
                'random_visits' => $result->random_visits,
                'planned_random_ratio' => $result->planned_random_ratio,
                'client_breakdown_all_url' => static::buildClientBreakdownUrl($result->id, 'all', $filters ?? []),
                'client_breakdown_visited_url' => static::buildClientBreakdownUrl($result->id, 'visited', $filters ?? []),
                'client_breakdown_unvisited_url' => static::buildClientBreakdownUrl($result->id, 'unvisited', $filters ?? []),
                'visit_breakdown_url' => static::buildVisitBreakdownUrl($result->id, $filters ?? []),
                'clinic_visit_breakdown_url' => static::buildClinicVisitBreakdownUrl($result->id, $filters ?? []),
            ];

            $newResults->push(collect($newResult));
        }

        return $newResults;
    }


    /**
     * Get client breakdown all URL attribute
     */
    public function getClientBreakdownAllUrlAttribute(): string
    {
        return self::buildClientBreakdownUrl($this->id, 'all', $this->filters ?? []);
    }

    /**
     * Get client breakdown visited URL attribute
     */
    public function getClientBreakdownVisitedUrlAttribute(): string
    {
        return self::buildClientBreakdownUrl($this->id, 'visited', $this->filters ?? []);
    }

    /**
     * Get client breakdown unvisited URL attribute
     */
    public function getClientBreakdownUnvisitedUrlAttribute(): string
    {
        return self::buildClientBreakdownUrl($this->id, 'unvisited', $this->filters ?? []);
    }

    /**
     * Get visit breakdown URL attribute
     */
    public function getVisitBreakdownUrlAttribute(): string
    {
        return self::buildVisitBreakdownUrl($this->id, $this->filters ?? []);
    }

    /**
     * Get clinic visit breakdown URL attribute
     */
    public function getClinicVisitBreakdownUrlAttribute(): string
    {
        return self::buildClinicVisitBreakdownUrl($this->id, $this->filters ?? []);
    }

    /**
     * Build URL for client breakdown
     */
    public static function buildClientBreakdownUrl($recordId, string $status = 'all', array $filters = []): string
    {
        $fromDate = $filters['from_date'] ?? today()->firstOfMonth()->toDateString();
        $toDate = $filters['to_date'] ?? today()->toDateString();
        $clientTypeId = $filters['client_type_id'] ?? ClientType::PM;

        $params = [
            'from_date' => $fromDate,
            'to_date' => $toDate,
            'status' => $status,
            'user_id' => $recordId,
            'client_type_id' => $clientTypeId
        ];

        return route('filament.admin.resources.client-breakdowns.index', $params);
    }

    /**
     * Build URL for visit breakdown
     */
    public static function buildVisitBreakdownUrl($recordId, array $filters = []): string
    {
        $fromDate = $filters['from_date'] ?? today()->firstOfMonth()->toDateString();
        $toDate = $filters['to_date'] ?? today()->toDateString();
        $clientTypeId = $filters['client_type_id'] ?? ClientType::PM;

        $tableFilters = [
            'visit_date' => [
                'from_date' => $fromDate,
                'to_date' => $toDate
            ],
            'status' => [
                'value' => 'visited'
            ],
            'client_type_id' => [
                'value' => [$clientTypeId]
            ]
        ];

        $params = [
            'breakdown' => 'true',
            'user_id' => [$recordId],
            'tableFilters' => $tableFilters
        ];

        return route('filament.admin.resources.visits.index', $params);
    }

    /**
     * Build URL for clinic visit breakdown
     */
    public static function buildClinicVisitBreakdownUrl($recordId, array $filters = []): string
    {
        $fromDate = $filters['from_date'] ?? today()->firstOfMonth()->toDateString();
        $toDate = $filters['to_date'] ?? today()->toDateString();
        $clientTypeId = $filters['client_type_id'] ?? ClientType::PM;

        $tableFilters = [
            'client_type_id' => [
                'value' => [$clientTypeId]
            ],
            'visit_date' => [
                'from_date' => $fromDate,
                'to_date' => $toDate
            ],
            'status' => [
                'value' => 'visited'
            ]
        ];

        $params = [
            'breakdown' => 'true',
            'user_id' => [$recordId],
            'tableFilters' => $tableFilters
        ];

        return route('filament.admin.resources.visits.index', $params);
    }
}
