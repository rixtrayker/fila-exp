<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

class OptimizeAppPerformance
{
    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Artisan::call('optimize:clear');
        Artisan::call('optimize');
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        // Artisan::call('icons:cache');
    }
}
