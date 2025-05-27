<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\FetchOfficialHolidays;
use Carbon\Carbon;

class FetchOfficialHolidaysCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'holidays:fetch
                            {country? : The country code to fetch holidays for}
                            {--year= : The specific year to fetch holidays for}
                            {--last-n-years= : Number of previous years to fetch holidays for (including current year)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch official holidays for specified year(s) and store them in the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $country = $this->argument('country');
        $year = $this->option('year');
        $lastNYears = $this->option('last-n-years');

        if ($lastNYears && $year) {
            $this->error('Cannot use both --year and --last-n-years options together.');
            return self::FAILURE;
        }

        if ($lastNYears) {
            $lastNYears = (int) $lastNYears;
            if ($lastNYears < 1) {
                $this->error('The --last-n-years option must be a positive integer.');
                return self::FAILURE;
            }

            $currentYear = Carbon::now()->year;
            $startYear = $currentYear - ($lastNYears - 1);

            $this->info("Fetching holidays for years {$startYear} to {$currentYear}...");

            for ($year = $startYear; $year <= $currentYear; $year++) {
                $this->info("Dispatching job for year {$year}...");
                if ($country) {
                    FetchOfficialHolidays::dispatch($country, $year);
                } else {
                    FetchOfficialHolidays::dispatch('EG', $year);
                }
            }
        } else {
            $this->info('Dispatching job to fetch official holidays...');
            if ($country) {
                FetchOfficialHolidays::dispatch($country, $year);
            } else {
                FetchOfficialHolidays::dispatch('EG', $year);
            }
        }

        $this->info('FetchOfficialHolidays job(s) dispatched successfully.');

        return self::SUCCESS;
    }
}
