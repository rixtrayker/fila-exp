<?php

namespace App\Services\Stats;

use App\Models\Visit;
use App\Models\ClientType;
use App\Helpers\DateHelper;
use App\Services\VisitCacheService;
use Illuminate\Support\Facades\Auth;

class CoverageStatsService
{
    /**
     * Get coverage data for a specific type (am, pm, pharmacy)
     */
    public static function getCoverageData(string $type): array
    {
        $today = DateHelper::today();
        $userId = Auth::id();
        $date = $today->format('Y-m-d');

        $cacheType = match($type) {
            'am' => VisitCacheService::TYPE_COVERAGE_AM,
            'pm' => VisitCacheService::TYPE_COVERAGE_PM,
            'pharmacy' => VisitCacheService::TYPE_COVERAGE_PHARMACY,
            default => VisitCacheService::TYPE_COVERAGE_AM,
        };

        return VisitCacheService::getCachedCoverageData($userId, $date, $cacheType, function () use ($today, $type) {
            return self::getVisitData($today, $type);
        });
    }

    /**
     * Get visit data for a specific date and type
     */
    public static function getVisitData($date, $type): array
    {
        $clientTypeIds = self::getClientTypeIds($type);

        $query = Visit::query()
            ->whereDate('visit_date', $date)
            ->whereHas('client', function ($q) use ($clientTypeIds) {
                $q->whereIn('client_type_id', $clientTypeIds);
            });

        $totalVisits = $query->count();
        $visitedVisits = (clone $query)->where('status', 'visited')->count();
        $pendingVisits = (clone $query)->whereIn('status', ['pending', 'planned'])->count();
        $missedVisits = (clone $query)->where('status', 'cancelled')->count();

        return [
            'labels' => ['Visited', 'Pending', 'Missed'],
            'data' => [$visitedVisits, $pendingVisits, $missedVisits],
            'total' => $totalVisits,
            'type' => $type,
        ];
    }

    /**
     * Get coverage heading with statistics
     */
    public static function getCoverageHeading(string $type): string
    {
        $data = self::getCoverageData($type);
        $typeLabel = ucfirst($type);
        $total = array_sum($data['data']);
        $visited = $data['data'][0];

        $percentage = $total > 0 ? round(($visited / $total) * 100, 1) : 0;

        return "Coverage Report - {$typeLabel} ({$visited}/{$total} - {$percentage}%)";
    }

    /**
     * Get client type IDs for a specific coverage type
     */
    private static function getClientTypeIds(string $type): array
    {
        return match($type) {
            'am' => ClientType::whereIn('name', [
                'Hospital',
                'Resuscitation Centre',
                'Incubators Centre'
            ])->pluck('id')->toArray(),

            'pm' => ClientType::whereIn('name', [
                'Clinic',
                'Poly Clinic'
            ])->pluck('id')->toArray(),

            'pharmacy' => ClientType::whereIn('name', [
                'Pharmacy'
            ])->pluck('id')->toArray(),

            default => ClientType::whereIn('name', [
                'Hospital',
                'Resuscitation Centre',
                'Incubators Centre'
            ])->pluck('id')->toArray(),
        };
    }
}
