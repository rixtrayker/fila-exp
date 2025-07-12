<?php

namespace App\Console\Commands;

use App\Services\VisitStatsCacheService;
use Illuminate\Console\Command;

class ClearOldVisitStatsCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'visit-stats:clear-old-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear old visit stats cache entries';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Clearing old visit stats cache...');

        VisitStatsCacheService::clearOldCache();

        $this->info('Old visit stats cache cleared successfully!');

        return Command::SUCCESS;
    }
}
