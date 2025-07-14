<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
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
    public ?int $year;

    /**
     * Create a new job instance.
     *
     * @param string $countryCode The ISO 3166-1 alpha-2 country code (default: 'EG')
     * @param string|null $year The year to fetch holidays for (default: null, will use current year)
     */
    public function __construct(string $countryCode = 'EG', ?int $year = null)
    {
        $this->countryCode = $countryCode;
        $this->year = $year;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::channel('fetch-holidays')->info("Starting FetchOfficialHolidays job for country code: {$this->countryCode}");
        try {
            $year = $this->year ? $this->year : Carbon::now()->year;

            if ($year < 2020 || $year > 2040 || !is_int($year)) {
                Log::channel('fetch-holidays')->error("Invalid year: {$year}");
                return;
            }

            // Fetch country model using the provided code
            $country = Country::where('code', $this->countryCode)->first();

            if (!$country) {
                Log::channel('fetch-holidays')->error("Country with code {$this->countryCode} not found.");
                return;
            }


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
                $savedHoliday = OfficialHoliday::updateOrCreate(
                    [
                        'country_id' => $country->id,
                        'date' => $holidayDate->toDateString(),
                    ],
                    [
                        'name' => $holiday['name'], // Use English name
                        'description' => $holiday['localName'], // Use local name as description
                        'online' => true,
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
            }

            Log::channel('fetch-holidays')->info("Successfully processed {$savedCount} holidays for {$this->countryCode} for year {$year}.");

        } catch (\Exception $e) {
            Log::channel('fetch-holidays')->error("Error fetching holidays for {$this->countryCode}: " . $e->getMessage(), ['exception' => $e]);
        }
    }
}
