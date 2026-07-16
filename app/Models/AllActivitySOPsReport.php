<?php

namespace App\Models;

use App\Services\AllActivitySOPsReportService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class AllActivitySOPsReport extends Model
{
    // This model is backed by a service (query builder), not a table
    public $timestamps = false;

    public $incrementing = false;

    protected $table = 'all_activity_sops_reports'; // Add table name for compatibility

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'name',
        'evaluation_role_id',
        'role_name',
        'working_days',
        'actual_working_days',
        'am_visits',
        'am_daily_target',
        'am_monthly_target',
        'am_sops',
        'pm_visits',
        'pm_daily_target',
        'pm_monthly_target',
        'pm_sops',
        'ph_visits',
        'ph_daily_target',
        'ph_monthly_target',
        'ph_sops',
        'total_visits',
        'call_rate',
    ];

    protected $casts = [
        'id' => 'integer',
        'evaluation_role_id' => 'integer',
        'working_days' => 'integer',
        'actual_working_days' => 'integer',
        'am_visits' => 'integer',
        'am_daily_target' => 'float',
        'am_monthly_target' => 'float',
        'am_sops' => 'float',
        'pm_visits' => 'integer',
        'pm_daily_target' => 'float',
        'pm_monthly_target' => 'float',
        'pm_sops' => 'float',
        'ph_visits' => 'integer',
        'ph_daily_target' => 'float',
        'ph_monthly_target' => 'float',
        'ph_sops' => 'float',
        'total_visits' => 'integer',
        'call_rate' => 'float',
    ];

    /**
     * Get report rows with filters applied from any source
     * (Filament table filter state, request parameters, plain arrays).
     */
    public static function getReportDataWithFilters(array $filters): Collection
    {
        return app(AllActivitySOPsReportService::class)
            ->getReportData(static::normalizeFilters($filters));
    }

    /**
     * Normalize filters from any source into report service inputs.
     */
    public static function normalizeFilters(array $filters): array
    {
        $dateRange = $filters['date_range'] ?? [];

        $fromDate = $dateRange['from_date'] ?? $filters['from_date'] ?? null;
        $toDate = $dateRange['to_date'] ?? $filters['to_date'] ?? null;

        $userFilter = $filters['user_id'] ?? null;

        if (is_array($userFilter) && array_key_exists('values', $userFilter)) {
            $userFilter = $userFilter['values'];
        }

        $userFilter = array_filter((array) ($userFilter ?? []));
        $evaluationRoleId = $filters['evaluation_role_id'] ?? null;

        if (is_array($evaluationRoleId)) {
            $evaluationRoleId = $evaluationRoleId['value'] ?? null;
        }

        return [
            'from_date' => $fromDate
                ? Carbon::parse($fromDate)->toDateString()
                : today()->startOfMonth()->toDateString(),
            'to_date' => $toDate
                ? Carbon::parse($toDate)->toDateString()
                : today()->toDateString(),
            'user_id' => array_map('intval', array_values($userFilter)),
            'evaluation_role_id' => $evaluationRoleId !== null && $evaluationRoleId !== ''
                ? (int) $evaluationRoleId
                : null,
        ];
    }
}
