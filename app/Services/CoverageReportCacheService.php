<?php

namespace App\Services;

use App\Helpers\DateHelper;
use App\Models\Visit;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CoverageReportCacheService
{
    private const CACHE_TTL = 3600; // 1 hour
    private const CACHE_TYPES = ['am', 'pm', 'pharmacy'];

    /**
     * Get cached data for a user, date, and type
     */
    public static function getCachedData($userId, $date, $type, \Closure $callback)
    {
        $cacheKey = self::makeCacheKey($userId, $date, $type);
        return Cache::remember($cacheKey, self::CACHE_TTL, $callback);
    }

    /**
     * Set cached data for a user, date, and type
     */
    public static function setCachedData($userId, $date, $type, $data): void
    {
        $cacheKey = self::makeCacheKey($userId, $date, $type);
        Cache::put($cacheKey, $data, self::CACHE_TTL);
    }

    /**
     * Forget cached data for a user, date, and type
     */
    public static function forgetCachedData($userId, $date, $type): void
    {
        $cacheKey = self::makeCacheKey($userId, $date, $type);
        Cache::forget($cacheKey);
    }

    /**
     * Clear cache for a specific user and date
     */
    public static function clearCacheForUserAndDate(int $userId, string $date): void
    {
        foreach (self::CACHE_TYPES as $type) {
            self::forgetCachedData($userId, $date, $type);
        }
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

    /**
     * Helper to build cache key
     */
    private static function makeCacheKey($userId, $date, $type): string
    {
        return "coverage_report_{$date}_{$userId}_{$type}";
    }
}
