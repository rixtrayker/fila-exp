<?php

namespace App\Observers;

use App\Models\Visit;
use App\Services\VisitCacheService;

class VisitObserver
{
    /**
     * Handle the Visit "created" event.
     */
    public function created(Visit $visit): void
    {
        VisitCacheService::clearVisitCache($visit);
    }

    /**
     * Handle the Visit "updated" event.
     */
    public function updated(Visit $visit): void
    {
        VisitCacheService::clearVisitCache($visit);
    }

    /**
     * Handle the Visit "deleted" event.
     */
    public function deleted(Visit $visit): void
    {
        VisitCacheService::clearVisitCache($visit);
    }

    /**
     * Handle the Visit "restored" event.
     */
    public function restored(Visit $visit): void
    {
        VisitCacheService::clearVisitCache($visit);
    }

    /**
     * Handle the Visit "force deleted" event.
     */
    public function forceDeleted(Visit $visit): void
    {
        VisitCacheService::clearVisitCache($visit);
    }
}
