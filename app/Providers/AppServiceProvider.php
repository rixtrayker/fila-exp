<?php

namespace App\Providers;

use App\Models\Plan;
use App\Models\Visit;
use App\Observers\PlanObserver;
use App\Observers\VisitObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Plan::observe(PlanObserver::class);
        Visit::observe(VisitObserver::class);
    }
}
