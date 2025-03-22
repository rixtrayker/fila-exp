<?php

namespace App\Providers;

use App\Services\Stats\ClientStatsService;
use App\Services\Stats\OrderStatsService;
use App\Services\Stats\VisitStatsService;
use Illuminate\Support\ServiceProvider;

class StatsServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(VisitStatsService::class);
        $this->app->singleton(OrderStatsService::class);
        $this->app->singleton(ClientStatsService::class);
    }
}