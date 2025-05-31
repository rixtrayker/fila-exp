<?php

namespace App\Services;

use App\Helpers\LocationHelpers;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use App\Models\Client;
use Illuminate\Support\Facades\Log;

class LocationService
{
    public function validateVisitLocation(int $clientId, ?Collection $location): bool
    {
        if (!$location) {
            return false;
        }

        $client = Client::find($clientId);

        if (!$client || !$client->lat || !$client->lng) {
            return true;
        }

        $lat = $location->get('latitude');
        $lng = $location->get('longitude');
        $result = LocationHelpers::isValidDistance($lat, $lng, $client->lat, $client->lng);

        if (!$result) {
            $user = auth()->id();
            Log::info('location is not valid', ['userId' => $user, 'user_location' => ['lat' => $lat, 'lng' => $lng], 'client_location' => ['lat' => $client->lat, 'lng' => $client->lng]]);
        }

        return $result;
    }

    public function setLocation(string $sessionId, Collection $data): void
    {
        Session::put($sessionId . '-location', $data);
    }

    public function getLocation(string $sessionId): ?Collection
    {
        $location = Session::get($sessionId . '-location');
        return $location ? collect($location) : null;
    }
}
