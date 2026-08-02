<?php

namespace App\Services;

use App\Models\ClientType;
use App\Models\Role;
use App\Models\RoleVisitTarget;
use App\Models\Scopes\GetMineScope;
use App\Models\User;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Builds the "All Activity SOPs" report: one row per evaluated user, with every
 * required activity type (AM accounts / PM doctors / PH pharmacies) counted
 * side by side and targets resolved for the explicitly selected evaluation role
 * (role_visit_targets matrix, falling back to the global settings values).
 *
 * Counting rules intentionally mirror the GetSOPsAndCallRateData stored
 * procedure: only visits with status "visited", non-deleted, within the date
 * range; double visits are credited to BOTH the rep (user_id) and the
 * accompanying manager (second_user_id). Working days exclude weekends and
 * official holidays; actual working days additionally exclude approved office
 * work days, activity days and approved vacation days.
 */
class AllActivitySOPsReportService
{
    public const EVALUATION_ROLES = [
        'medical-rep',
        'district-manager',
        'area-manager',
        'country-manager',
    ];

    /**
     * Report sections keyed by row prefix => client type id.
     */
    public const SECTIONS = [
        'am' => ClientType::AM,
        'pm' => ClientType::PM,
        'ph' => ClientType::PH,
    ];

    public function getReportData(array $filters = []): Collection
    {
        $fromDate = Carbon::parse($filters['from_date'] ?? today()->startOfMonth())->toDateString();
        $toDate = Carbon::parse($filters['to_date'] ?? today())->toDateString();
        $evaluationRole = $this->getEvaluationRole($filters);

        $userIds = $this->getFilteredUserIds($filters, $evaluationRole);

        if (empty($userIds)) {
            return collect();
        }

        $users = User::withoutGlobalScopes()
            ->whereHas('roles', fn ($query) => $query->whereKey($evaluationRole->id))
            ->whereIn('id', $userIds)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->get();

        $workingDates = $this->getWorkingDates($fromDate, $toDate);
        $busyDates = $this->getBusyDatesByUser($fromDate, $toDate, $userIds);
        $visitCounts = $this->getVisitCountsByUserAndType($fromDate, $toDate, $userIds);

        $workingDays = count($workingDates);

        return $users
            ->map(function (User $user) use ($workingDates, $workingDays, $busyDates, $visitCounts, $evaluationRole) {
                $userBusyDates = $busyDates[$user->id] ?? [];
                $actualWorkingDays = count(array_filter(
                    $workingDates,
                    fn (string $date) => ! isset($userBusyDates[$date])
                ));

                $row = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'evaluation_role_id' => $evaluationRole->id,
                    'role_name' => $evaluationRole->name,
                    'working_days' => $workingDays,
                    'actual_working_days' => $actualWorkingDays,
                ];

                $totalVisits = 0;

                foreach (self::SECTIONS as $prefix => $clientTypeId) {
                    $visits = (int) ($visitCounts[$user->id][$clientTypeId] ?? 0);
                    $dailyTarget = RoleVisitTarget::resolveDailyTarget($evaluationRole->id, $clientTypeId);
                    $monthlyTarget = round($actualWorkingDays * $dailyTarget, 2);

                    $row["{$prefix}_visits"] = $visits;
                    $row["{$prefix}_daily_target"] = $dailyTarget;
                    $row["{$prefix}_monthly_target"] = $monthlyTarget;
                    $row["{$prefix}_sops"] = $monthlyTarget > 0
                        ? round(($visits / $monthlyTarget) * 100, 2)
                        : 0.0;

                    $totalVisits += $visits;
                }

                $row['total_visits'] = $totalVisits;
                $row['call_rate'] = $actualWorkingDays > 0
                    ? round($totalVisits / $actualWorkingDays, 2)
                    : 0.0;

                return $row;
            })
            ->values();
    }

    /**
     * Resolve and validate the role explicitly selected for this evaluation.
     */
    protected function getEvaluationRole(array $filters): Role
    {
        $roleId = filter_var(
            $filters['evaluation_role_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );

        if ($roleId === false || $roleId === null) {
            throw new InvalidArgumentException('An evaluation role must be selected.');
        }

        $role = Role::query()
            ->whereKey($roleId)
            ->whereIn('name', self::EVALUATION_ROLES)
            ->first();

        if ($role === null) {
            throw new InvalidArgumentException('The selected evaluation role is not supported.');
        }

        return $role;
    }

    /**
     * Get filtered user IDs based on permissions and filters
     * (same scoping as the existing SOPs and Call Rate report).
     */
    protected function getFilteredUserIds(array $filters, Role $evaluationRole): array
    {
        $userIds = GetMineScope::getUserIds();

        if (empty($userIds)) {
            $userIds = DB::table('users')->pluck('id')->toArray();
        }

        if (! empty($filters['user_id'])) {
            $requestedUserIds = array_map('intval', (array) $filters['user_id']);
            $userIds = array_intersect($userIds, $requestedUserIds);

            $invalidUserIds = User::withoutGlobalScopes()
                ->whereIn('id', $userIds)
                ->whereDoesntHave('roles', fn ($query) => $query->whereKey($evaluationRole->id))
                ->pluck('id')
                ->all();

            if ($invalidUserIds !== []) {
                throw new InvalidArgumentException(
                    'The selected evaluation role must belong to every evaluated user.'
                );
            }
        }

        return User::withoutGlobalScopes()
            ->whereIn('id', array_map('intval', array_values($userIds)))
            ->whereHas('roles', fn ($query) => $query->whereKey($evaluationRole->id))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    /**
     * Calendar working dates in range: excludes weekends and official holidays
     * (mirrors the stored procedure's working days calculation).
     *
     * @return array<int, string> list of Y-m-d dates
     */
    protected function getWorkingDates(string $fromDate, string $toDate): array
    {
        $holidays = DB::table('official_holidays')
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->filter(fn (string $date) => $date >= $fromDate && $date <= $toDate)
            ->flip()
            ->all();

        $dates = [];

        foreach (CarbonPeriod::create($fromDate, $toDate) as $date) {
            if (in_array($date->dayOfWeek, [Carbon::SATURDAY, Carbon::SUNDAY], true)) {
                continue;
            }

            $dateString = $date->toDateString();

            if (isset($holidays[$dateString])) {
                continue;
            }

            $dates[] = $dateString;
        }

        return $dates;
    }

    /**
     * Dates on which each user was busy (approved office work, activities,
     * approved vacations) and therefore not expected in the field.
     *
     * @return array<int, array<string, true>> user id => set of Y-m-d dates
     */
    protected function getBusyDatesByUser(string $fromDate, string $toDate, array $userIds): array
    {
        $busy = [];

        $officeWorks = DB::table('office_works')
            ->whereIn('user_id', $userIds)
            ->where('status', 'approved')
            ->whereDate('created_at', '>=', $fromDate)
            ->whereDate('created_at', '<=', $toDate)
            ->get(['user_id', 'created_at']);

        foreach ($officeWorks as $officeWork) {
            $busy[(int) $officeWork->user_id][Carbon::parse($officeWork->created_at)->toDateString()] = true;
        }

        $activities = DB::table('activities')
            ->whereIn('user_id', $userIds)
            ->whereDate('date', '>=', $fromDate)
            ->whereDate('date', '<=', $toDate)
            ->get(['user_id', 'date']);

        foreach ($activities as $activity) {
            $busy[(int) $activity->user_id][Carbon::parse($activity->date)->toDateString()] = true;
        }

        $vacations = DB::table('vacation_durations as vd')
            ->join('vacation_requests as vr', 'vd.vacation_request_id', '=', 'vr.id')
            ->where('vr.approved', 1)
            ->whereIn('vr.user_id', $userIds)
            ->whereDate('vd.start', '<=', $toDate)
            ->whereDate('vd.end', '>=', $fromDate)
            ->get(['vr.user_id', 'vd.start', 'vd.end']);

        foreach ($vacations as $vacation) {
            $start = max(Carbon::parse($vacation->start)->toDateString(), $fromDate);
            $end = min(Carbon::parse($vacation->end)->toDateString(), $toDate);

            foreach (CarbonPeriod::create($start, $end) as $day) {
                $busy[(int) $vacation->user_id][$day->toDateString()] = true;
            }
        }

        return $busy;
    }

    /**
     * Count visited, non-deleted visits per credited user and client type.
     * A visit is always credited to its owner (user_id); double visits are
     * additionally credited to the accompanying manager (second_user_id),
     * exactly like the GetSOPsAndCallRateData stored procedure.
     *
     * Only visits inside the credited user's accountable pool are counted:
     * when that user maintains a personal client list for the visit's client
     * type, the client must be on it; otherwise the area-derived pool applies.
     * See Client::scopeAccountablePool for the same rule on the Eloquent side.
     *
     * @return array<int, array<int, int>> user id => [client type id => count]
     */
    protected function getVisitCountsByUserAndType(string $fromDate, string $toDate, array $userIds): array
    {
        $baseQuery = fn () => DB::table('visits as v')
            ->join('clients as c', 'v.client_id', '=', 'c.id')
            ->where('v.status', 'visited')
            ->where('c.active', true)
            ->whereNull('v.deleted_at')
            ->whereDate('v.visit_date', '>=', $fromDate)
            ->whereDate('v.visit_date', '<=', $toDate);

        $repRows = $this->restrictToAccountablePool($baseQuery(), 'v.user_id')
            ->whereIn('v.user_id', $userIds)
            ->select('v.user_id as credited_user_id', 'c.client_type_id')
            ->selectRaw('COUNT(*) as visits_count')
            ->groupBy('v.user_id', 'c.client_type_id')
            ->get();

        $managerRows = $this->restrictToAccountablePool($baseQuery(), 'v.second_user_id')
            ->whereNotNull('v.second_user_id')
            ->whereIn('v.second_user_id', $userIds)
            ->select('v.second_user_id as credited_user_id', 'c.client_type_id')
            ->selectRaw('COUNT(*) as visits_count')
            ->groupBy('v.second_user_id', 'c.client_type_id')
            ->get();

        $counts = [];

        return $this->tallyVisitCounts($repRows->concat($managerRows), $counts);
    }

    /**
     * Restrict a visits query to the accountable pool of the user credited in
     * $creditedUserColumn.
     *
     * A visit counts when either:
     * - the credited user has no personal list for the visited client's type,
     *   and the client sits in one of their bricks (legacy area-derived pool);
     * - or the client is explicitly on the credited user's personal list.
     *
     * Personal lists are evaluated per client type so that a rep who curates
     * only, say, their pharmacy list is not penalised on the other sections.
     */
    protected function restrictToAccountablePool($query, string $creditedUserColumn)
    {
        return $query
            ->whereExists(function ($inTerritory) use ($creditedUserColumn) {
                $inTerritory
                    ->selectRaw('1')
                    ->from('user_bricks_view as accountable_brick')
                    ->whereColumn('accountable_brick.user_id', $creditedUserColumn)
                    ->whereColumn('accountable_brick.brick_id', 'c.brick_id');
            })
            ->where(function ($accountablePool) use ($creditedUserColumn) {
                $accountablePool
                    ->whereNotExists(fn ($hasList) => $this->personalListForVisitedTypeQuery($hasList, $creditedUserColumn))
                    ->orWhereExists(function ($onList) use ($creditedUserColumn) {
                        $onList
                            ->selectRaw('1')
                            ->from('client_user as selected_clients')
                            ->whereColumn('selected_clients.user_id', $creditedUserColumn)
                            ->whereColumn('selected_clients.client_id', 'c.id');
                    });
            });
    }

    /**
     * Whether the credited user maintains a personal list for the client type
     * of the visit being counted. Stale entries (clients that fell out of the
     * user's territory or were deactivated) do not make a list "maintained".
     */
    protected function personalListForVisitedTypeQuery($query, string $creditedUserColumn)
    {
        return $query
            ->selectRaw('1')
            ->from('client_user as list_entries')
            ->join('clients as list_clients', 'list_clients.id', '=', 'list_entries.client_id')
            ->whereColumn('list_entries.user_id', $creditedUserColumn)
            ->where('list_clients.active', true)
            ->whereColumn('list_clients.client_type_id', 'c.client_type_id')
            ->whereExists(function ($eligible) {
                $eligible
                    ->selectRaw('1')
                    ->from('user_bricks_view as list_bricks')
                    ->whereColumn('list_bricks.user_id', 'list_entries.user_id')
                    ->whereColumn('list_bricks.brick_id', 'list_clients.brick_id');
            });
    }

    /**
     * @param  array<int, array<int, int>>  $counts
     * @return array<int, array<int, int>>
     */
    protected function tallyVisitCounts(Collection $rows, array $counts): array
    {
        foreach ($rows as $row) {
            if ($row->client_type_id === null) {
                continue;
            }

            $userId = (int) $row->credited_user_id;
            $clientTypeId = (int) $row->client_type_id;

            $counts[$userId][$clientTypeId] = ($counts[$userId][$clientTypeId] ?? 0) + (int) $row->visits_count;
        }

        return $counts;
    }
}
