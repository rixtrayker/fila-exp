<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchOfficialHolidays;

class FetchOfficialHolidaysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holidays:fetch {country?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch official holidays for the next month and store them in the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Dispatching job to fetch official holidays...');

        $country = $this->argument('country');
        if ($country) {
            FetchOfficialHolidays::dispatch($country);
        } else {
            FetchOfficialHolidays::dispatch();
        }

        $this->info('FetchOfficialHolidays job dispatched successfully.');

        return Command::SUCCESS;
    }
}
