<?php

namespace App\Services;

use App\Helpers\DateHelper;
use App\Models\Visit;
use App\Models\User;
use App\Traits\HasCaching;

/**
 * Visit Stats Cache Service
 *
 * This service handles caching for visit statistics data.
 * It uses the HasCaching trait for common caching operations.
 */
class VisitStatsCacheService
{
    use HasCaching;

    private const CACHE_PREFIX = 'visit_stats';
    private const CACHE_TTL = 1800; // 30 minutes

    /**
     * Get cached visit stats for a user and date
     */
    public static function getCachedVisitStats($userId, $date, \Closure $callback)
    {
        $instance = new static();
        $cacheKey = $instance->makeCacheKey(self::CACHE_PREFIX, $date, $userId);
        return $instance->getCached($cacheKey, $callback, self::CACHE_TTL);
    }

    /**
     * Forget cached visit stats for a user and date
     */
    public static function forgetCachedVisitStats($userId, $date): void
    {
        $instance = new static();
        $cacheKey = $instance->makeCacheKey(self::CACHE_PREFIX, $date, $userId);
        $instance->forgetCached($cacheKey);
    }

    /**
     * Clear cache for a specific user and date
     */
    public static function clearCacheForUserAndDate(int $userId, string $date): void
    {
        $instance = new static();
        $cacheKey = $instance->makeCacheKey(self::CACHE_PREFIX, $date, $userId);
        $instance->forgetCached($cacheKey);
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
