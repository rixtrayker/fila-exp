<?php

namespace App\Filament\Resources\PlanResource;

use App\Models\Client;
use App\Models\ClientType;
use App\Models\Visit;
use App\Helpers\DateHelper;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Builder;

class ClientManager
{
    // Client cache management
    private static bool $isPrepared = false;
    private static array $allClients = [];
    private static array $clients = [];
    private static array $pmClients = [];
    private static array $pharmacyClients = [];
    private static array $clientTypes = [
        "am" => [ClientType::AM],
        "pm" => [ClientType::PM],
        "pharmacy" => [ClientType::PH],
    ];

    /**
     * Get clients by type from the cache
     */
    public static function getClients($type = null, $day = null): array
    {
        if (!self::$isPrepared) {
            self::prepareData();
        }

        if (auth()->user()->hasRole('district_manager') && $day) {
            $key = $type ? "{$day}-{$type}" : $day;
            return self::$clients[$key] ?? [];
        }

        $key = $type ?? 'all';
        return self::$clients[$key] ?? [];
    }

    /**
     * Load and cache client data
     */
    private static function prepareData(): void
    {
        $allClients = Client::inMyAreas()
            ->select("client_type_id", "name_en", "id")
            ->get();

        // Cache base client lists
        self::$clients["all"] = $allClients->pluck("name_en", "id")->toArray();
        self::$clients["am"] = $allClients
            ->whereIn("client_type_id", self::$clientTypes["am"])
            ->pluck("name_en", "id")
            ->toArray();
        self::$clients["pm"] = $allClients
            ->whereIn("client_type_id", self::$clientTypes["pm"])
            ->pluck("name_en", "id")
            ->toArray();
        self::$clients["pharmacy"] = $allClients
            ->whereIn("client_type_id", self::$clientTypes["pharmacy"])
            ->pluck("name_en", "id")
            ->toArray();

        if (auth()->user()->hasRole("district_manager")) {
            $dates = DateHelper::calculateVisitDates();
            $days = array_map(
                fn($date) => Str::lower(Carbon::parse($date)->format("D")),
                $dates
            );
            $plannedClients = Visit::districtManagerClients();

            foreach ($dates as $i => $date) {
                $day = $days[$i];
                $dayClients = $plannedClients->where('visit_date', $date);

                // Store day-specific clients with name_en as values
                self::$clients[$day] = $dayClients
                    ->pluck("client.name_en", "client_id")
                    ->toArray();

                // Filter day-specific clients by type
                self::$clients["{$day}-am"] = $dayClients
                    ->whereIn("client.client_type_id", $amTypeIDs)
                    ->pluck("client.name_en", "client_id")
                    ->toArray();
                self::$clients["{$day}-pm"] = $dayClients
                    ->whereIn("client.client_type_id", $pmTypeIDs)
                    ->pluck("client.name_en", "client_id")
                    ->toArray();
                self::$clients["{$day}-pharmacy"] = $dayClients
                    ->whereIn("client.client_type_id", $pharmacyTypeIDs)
                    ->pluck("client.name_en", "client_id")
                    ->toArray();
            }
        }

        self::$isPrepared = true;
    }

    /**
     * Search clients by name
     */

    public static function searchQuery(string $search): Builder
    {
        return Client::inMyAreas()
            ->where("name_en", "like", "%{$search}%")
            ->orWhere("name_ar", "like", "%{$search}%");
    }

    public static function searchClients(string $search): array
    {
        return self::searchQuery($search)->pluck("name_en", "id")->toArray();
    }

    public static function searchClientsByType(string $search, string $type): array
    {
        $typeIds = self::$clientTypes[$type];

        return self::searchQuery($search)
            ->whereIn("client.client_type_id", $typeIds)
            ->pluck("name_en", "id");
    }
}
