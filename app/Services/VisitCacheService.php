<?php

namespace App\Services;

use App\Helpers\DateHelper;
use App\Models\Visit;
use App\Models\User;

/**
 * Centralized Visit Cache Service
 * 
 * This service handles all visit-related caching operations including:
 * - Visit statistics
 * - Coverage reports
 * - Any other visit-related data
 */
class VisitCacheService extends BaseCacheService
{
    // Cache types for different visit data
    public const TYPE_VISIT_STATS = 'visit_stats';
    public const TYPE_COVERAGE_AM = 'coverage_am';
    public const TYPE_COVERAGE_PM = 'coverage_pm';
    public const TYPE_COVERAGE_PHARMACY = 'coverage_pharmacy';

    // Cache configuration
    private const CACHE_TTL_VISIT_STATS = 1800; // 30 minutes
    private const CACHE_TTL_COVERAGE = 3600; // 1 hour

    public function __construct()
    {
        parent::__construct('visit_cache', 1800);
    }

    /**
     * Get cached visit stats for a user and date
     */
    public function getVisitStats(int $userId, string $date, \Closure $callback)
    {
        $cacheKey = $this->buildCacheKey(self::TYPE_VISIT_STATS, $date, $userId);
        return $this->getCached($cacheKey, $callback, self::CACHE_TTL_VISIT_STATS);
    }

    /**
     * Get cached coverage report data
     */
    public function getCoverageData(int $userId, string $date, string $type, \Closure $callback)
    {
        $cacheKey = $this->buildCacheKey($type, $date, $userId);
        return $this->getCached($cacheKey, $callback, self::CACHE_TTL_COVERAGE);
    }

    /**
     * Clear cache for a specific visit (affects both stats and coverage)
     */
    public function clearCacheForVisit(Visit $visit): void
    {
        $date = $visit->visit_date->format('Y-m-d');
        $userId = $visit->user_id;
        $this->clearCacheForUserAndAncestors($date, $userId);
    }

    /**
     * Clear cache for a specific user and date
     */
    public function clearCacheForUserAndDate(int $userId, string $date): void
    {
        // Clear coverage cache types
        $coverageTypes = [
            self::TYPE_COVERAGE_AM,
            self::TYPE_COVERAGE_PM,
            self::TYPE_COVERAGE_PHARMACY,
        ];

        foreach ($coverageTypes as $type) {
            $cacheKey = $this->buildCacheKey($type, $date, $userId);
            $this->forgetCached($cacheKey);
        }

        // Clear visit stats cache keys
        $visitStatsKeys = [
            'visit_stats_visits',
            'visit_stats_daily_plan',
            'visit_stats_clients_count',
            'visit_stats_achieved',
            'visit_stats_planned_vs_actual',
            'visit_stats_done_plan',
            'visit_stats_overview',
        ];

        foreach ($visitStatsKeys as $keyType) {
            $cacheKey = $this->makePublicCacheKey($keyType, $userId, $date);
            $this->forgetCached($cacheKey);
        }
    }

    /**
     * Clear cache for user and all ancestors (for hierarchical data)
     */
    public function clearCacheForUserAndAncestors(string $date, int $userId): void
    {
        $user = User::find($userId);
        
        while ($user) {
            $this->clearCacheForUserAndDate($user->id, $date);
            
            if (!$user->parent_id) {
                break;
            }
            
            $user = User::find($user->parent_id);
        }
    }

    /**
     * Clear cache for all users on a specific date
     */
    public function clearCacheForAllUsersOnDate(string $date): void
    {
        $users = User::all();
        foreach ($users as $user) {
            $this->clearCacheForUserAndDate($user->id, $date);
        }
    }

    /**
     * Clear old cache entries (previous dates)
     */
    public function clearOldCache(): void
    {
        $today = DateHelper::today();
        $yesterday = $today->copy()->subDay();

        $users = User::all();
        foreach ($users as $user) {
            $oldDate = $yesterday->copy();
            while ($oldDate->isBefore($today)) {
                $this->clearCacheForUserAndDate($user->id, $oldDate->format('Y-m-d'));
                $oldDate->addDay();
            }
        }
    }

    /**
     * Build cache key for visit-related data
     */
    private function buildCacheKey(string $type, string $date, int $userId): string
    {
        return $this->makeCacheKey($this->cachePrefix, $type, $date, $userId);
    }

    /**
     * Public method to make cache keys
     */
    public function makePublicCacheKey(...$parts): string
    {
        return $this->makeCacheKey('visit_cache', ...$parts);
    }

    /**
     * Public method to get cached data
     */
    public function getPublicCached(string $key, \Closure $callback, int $ttl = 1800)
    {
        return $this->getCached($key, $callback, $ttl);
    }

    /**
     * Static helper methods for backward compatibility
     */

    /**
     * Get cached visit stats (static helper)
     */
    public static function getCachedVisitStats(int $userId, string $date, \Closure $callback)
    {
        return app(self::class)->getVisitStats($userId, $date, $callback);
    }

    /**
     * Get cached coverage data (static helper)
     */
    public static function getCachedCoverageData(int $userId, string $date, string $type, \Closure $callback)
    {
        return app(self::class)->getCoverageData($userId, $date, $type, $callback);
    }

    /**
     * Clear cache for visit (static helper)
     */
    public static function clearVisitCache(Visit $visit): void
    {
        app(self::class)->clearCacheForVisit($visit);
    }

    /**
     * Clear old cache entries (static helper)
     */
    public static function clearOldCacheEntries(): void
    {
        app(self::class)->clearOldCache();
    }
}