<?php

namespace App\Traits;

use Illuminate\Support\Facades\Cache;

trait HasCaching
{
    /**
     * Get cached data with a callback for generation
     */
    protected function getCached(string $key, \Closure $callback, int $ttl = 3600)
    {
        return Cache::remember($key, $ttl, $callback);
    }

    /**
     * Set cached data
     */
    protected function setCached(string $key, $data, int $ttl = 3600): void
    {
        Cache::put($key, $data, $ttl);
    }

    /**
     * Forget cached data
     */
    protected function forgetCached(string $key): void
    {
        Cache::forget($key);
    }

    /**
     * Clear multiple cache keys
     */
    protected function clearMultiple(array $keys): void
    {
        foreach ($keys as $key) {
            $this->forgetCached($key);
        }
    }



    /**
     * Check if cache key exists
     */
    protected function hasCached(string $key): bool
    {
        return Cache::has($key);
    }

    /**
     * Get cache key with prefix
     */
    protected function makeCacheKey(string $prefix, ...$parts): string
    {
        return $prefix . '_' . implode('_', array_filter($parts));
    }
}
