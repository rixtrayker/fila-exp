<?php

namespace App\Services;

use App\Helpers\LocationHelpers;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use App\Models\Client;

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

        return LocationHelpers::isValidDistance($lat, $lng, $client->latitude, $client->longitude);
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
