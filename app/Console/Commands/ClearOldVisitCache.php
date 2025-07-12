<?php

namespace App\Console\Commands;

use App\Services\VisitCacheService;
use Illuminate\Console\Command;

class ClearOldVisitCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visit-cache:clear-old';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear old visit-related cache entries (stats and coverage)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing old visit cache entries...');

        VisitCacheService::clearOldCacheEntries();

        $this->info('Old visit cache entries cleared successfully!');

        return Command::SUCCESS;
    }
}