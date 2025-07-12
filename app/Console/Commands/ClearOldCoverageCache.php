<?php

namespace App\Console\Commands;

use App\Services\CoverageReportCacheService;
use Illuminate\Console\Command;

class ClearOldCoverageCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coverage:clear-old-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear old coverage report cache entries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing old coverage report cache...');

        CoverageReportCacheService::clearOldCache();

        $this->info('Old coverage report cache cleared successfully!');

        return Command::SUCCESS;
    }
}
