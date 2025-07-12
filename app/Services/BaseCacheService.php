<?php

namespace App\Services;

use App\Traits\HasCaching;

abstract class BaseCacheService
{
    use HasCaching;

    protected string $cachePrefix;
    protected int $cacheTtl;

    public function __construct(string $cachePrefix = '', int $cacheTtl = 3600)
    {
        $this->cachePrefix = $cachePrefix;
        $this->cacheTtl = $cacheTtl;
    }

    /**
     * Get cached data with the service's prefix and TTL
     */
    protected function getCachedWithPrefix(string $key, \Closure $callback)
    {
        $fullKey = $this->makeCacheKey($this->cachePrefix, $key);
        return $this->getCached($fullKey, $callback, $this->cacheTtl);
    }

    /**
     * Set cached data with the service's prefix and TTL
     */
    protected function setCachedWithPrefix(string $key, $data): void
    {
        $fullKey = $this->makeCacheKey($this->cachePrefix, $key);
        $this->setCached($fullKey, $data, $this->cacheTtl);
    }

    /**
     * Forget cached data with the service's prefix
     */
    protected function forgetCachedWithPrefix(string $key): void
    {
        $fullKey = $this->makeCacheKey($this->cachePrefix, $key);
        $this->forgetCached($fullKey);
    }

    /**
     * Clear all cache entries for this service
     */
    protected function clearAllCached(): void
    {
        // This would need to be implemented based on the cache driver
        // For now, we'll use a simple approach
        $this->clearByPrefix($this->cachePrefix);
    }

    /**
     * Clear cache by prefix (basic implementation)
     */
    protected function clearByPrefix(string $prefix): void
    {
        // Note: This is a simplified implementation
        // In a real application, you might want to use cache tags or Redis patterns
        // For now, this serves as a placeholder for more sophisticated cache clearing
    }
}
