<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Country;
use App\Models\OfficialHoliday;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FetchOfficialHolidays implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $countryCode;

    /**
     * Create a new job instance.
     *
     * @param string $countryCode The ISO 3166-1 alpha-2 country code (default: 'EG')
     */
    public function __construct(string $countryCode = 'EG')
    {
        $this->countryCode = $countryCode;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('fetch-holidays')->info("Starting FetchOfficialHolidays job for country code: {$this->countryCode}");
        try {
            $nextMonth = Carbon::now()->addMonth();
            $year = $nextMonth->year;
            $month = $nextMonth->month;

            // Fetch country model using the provided name
            $country = Country::where('name', $this->countryCode)->first();

            if (!$country) {
                Log::channel('fetch-holidays')->error("Country with code {$this->countryCode} not found.");
                return;
            }

            Log::channel('fetch-holidays')->info("Found country: ID={$country->id}, Name={$country->name}");

            // Fetch holidays from Nager.Date API for the specific year and country
            $apiUrl = "https://date.nager.at/api/v3/PublicHolidays/{$year}/{$this->countryCode}";
            Log::channel('fetch-holidays')->info("Fetching holidays from API: {$apiUrl}");
            $response = Http::get($apiUrl);

            if ($response->failed()) {
                Log::channel('fetch-holidays')->error("Failed to fetch holidays from Nager.Date API for {$this->countryCode}: " . $response->body());
                return;
            }

            $holidays = $response->json();
            Log::channel('fetch-holidays')->info("API returned " . count($holidays) . " holidays for {$this->countryCode} in {$year}.");

            if (empty($holidays)) {
                Log::channel('fetch-holidays')->info("No holidays found for {$this->countryCode} in {$year}.");
                return;
            }

            $savedCount = 0;
            foreach ($holidays as $holiday) {
                $holidayDate = Carbon::parse($holiday['date']);
                Log::channel('fetch-holidays')->debug("Processing holiday from API: ", $holiday);

                // Only process holidays for the next month
                if ($holidayDate->year == $year && $holidayDate->month == $month) {
                    Log::channel('fetch-holidays')->info("Attempting to save holiday: {$holiday['name']} ({$holidayDate->toDateString()}) for country ID: {$country->id}");
                    $savedHoliday = OfficialHoliday::updateOrCreate(
                        [
                            'country_id' => $country->id,
                            'date' => $holidayDate->toDateString(),
                        ],
                        [
                            'name' => $holiday['name'], // Use English name
                            'description' => $holiday['localName'], // Use local name as description
                            'date' => $holidayDate->toDateString(),
                            'online' => true,
                            'country_id' => $country->id,
                        ]
                    );
                    if ($savedHoliday->wasRecentlyCreated) {
                        Log::channel('fetch-holidays')->info("Created new holiday record with ID: {$savedHoliday->id}");
                    } elseif ($savedHoliday->wasChanged()) {
                        Log::channel('fetch-holidays')->info("Updated existing holiday record with ID: {$savedHoliday->id}");
                    } else {
                         Log::channel('fetch-holidays')->info("Holiday record already exists and is unchanged: ID: {$savedHoliday->id}");
                    }
                    $savedCount++;
                } else {
                     Log::channel('fetch-holidays')->debug("Skipping holiday: Date {$holidayDate->toDateString()} is not in the target month {$year}-{$month}.");
                }
            }

            Log::channel('fetch-holidays')->info("Successfully processed {$savedCount} holidays for {$this->countryCode} for {$nextMonth->format('Y-m')}.");

        } catch (\Exception $e) {
            Log::channel('fetch-holidays')->error("Error fetching holidays for {$this->countryCode}: " . $e->getMessage(), ['exception' => $e]);
        }
    }
}
