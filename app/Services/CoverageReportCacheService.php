<?php

namespace App\Services;

use App\Helpers\DateHelper;
use App\Models\Visit;
use App\Models\User;
use App\Traits\HasCaching;

/**
 * Coverage Report Cache Service
 *
 * This service handles caching for coverage report data.
 * It uses the HasCaching trait for common caching operations.
 *
 * Alternative approach: Could extend BaseCacheService for more advanced features
 * class CoverageReportCacheService extends BaseCacheService
 * {
 *     public function __construct()
 *     {
 *         parent::__construct('coverage_report', 3600);
 *     }
 * }
 */
class CoverageReportCacheService
{
    use HasCaching;

    private const CACHE_PREFIX = 'coverage_report';
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_TYPES = ['am', 'pm', 'pharmacy'];

    /**
     * Get cached data for a user, date, and type
     */
    public static function getCachedData($userId, $date, $type, \Closure $callback)
    {
        $instance = new static();
        $cacheKey = $instance->makeCacheKey(self::CACHE_PREFIX, $date, $userId, $type);
        return $instance->getCached($cacheKey, $callback, self::CACHE_TTL);
    }

    /**
     * Forget cached data for a user, date, and type
     */
    public static function forgetCachedData($userId, $date, $type): void
    {
        $instance = new static();
        $cacheKey = $instance->makeCacheKey(self::CACHE_PREFIX, $date, $userId, $type);
        $instance->forgetCached($cacheKey);
    }

    /**
     * Clear cache for a specific user and date
     */
    public static function clearCacheForUserAndDate(int $userId, string $date): void
    {
        $instance = new static();
        $keys = array_map(
            fn($type) => $instance->makeCacheKey(self::CACHE_PREFIX, $date, $userId, $type),
            self::CACHE_TYPES
        );
        $instance->clearMultiple($keys);
    }

    /**
     * Clear cache for a specific visit
     */
    public static function clearCacheForVisit(Visit $visit): void
    {
        $date = $visit->visit_date->format('Y-m-d');
        $userId = $visit->user_id;
        self::clearCacheForUserAndAncestors($date, $userId);
    }

    /**
     * Clear cache for user and all ancestors
     */
    public static function clearCacheForUserAndAncestors($date, $userId): void
    {
        $user = User::find($userId);
        while ($user) {
            self::clearCacheForUserAndDate($user->id, $date);
            if (!$user->parent_id) {
                break;
            }
            $user = User::find($user->parent_id);
        }
    }

    /**
     * Clear cache for all users on a specific date
     */
    public static function clearCacheForAllUsersOnDate(string $date): void
    {
        $users = User::all();
        foreach ($users as $user) {
            self::clearCacheForUserAndDate($user->id, $date);
        }
    }

    /**
     * Clear old cache entries (previous dates)
     */
    public static function clearOldCache(): void
    {
        $today = DateHelper::today();
        $yesterday = $today->copy()->subDay();

        $users = User::all();
        foreach ($users as $user) {
            $oldDate = $yesterday;
            while ($oldDate->isBefore($today)) {
                self::clearCacheForUserAndDate($user->id, $oldDate->format('Y-m-d'));
                $oldDate->addDay();
            }
        }
    }
}
