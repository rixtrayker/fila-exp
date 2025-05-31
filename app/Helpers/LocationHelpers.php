<?php

namespace App\Helpers;

use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class LocationHelpers
{
    public static function calculateDistance(float $latitude1, float $longitude1, float $latitude2, float $longitude2): float
    {
        // accurate distance in km
        $earthRadius = 6371; // Radius of the earth in km
        $dLat = deg2rad($latitude2 - $latitude1);
        $dLon = deg2rad($longitude2 - $longitude1);

        $a = sin($dLat/2) * sin($dLat/2) +
            cos(deg2rad($latitude1)) * cos(deg2rad($latitude2)) *
            sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        $distance = $earthRadius * $c;
        return $distance;
    }

    // public static function getDistance($latitude1, $longitude1, $latitude2, $longitude2)
    // {
    //     $distance = self::calculateDistance($latitude1, $longitude1, $latitude2, $longitude2);
    //     return $distance;
    // }

    public static function getDistanceInMeters($latitude1, $longitude1, $latitude2, $longitude2)
    {
        $distance = self::calculateDistance($latitude1, $longitude1, $latitude2, $longitude2);
        return $distance * 1000;
    }

    public static function isValidDistance(float $lat1, float $lng1, float $lat2, float $lng2): bool
    {
        $allowedDistance = Setting::getSetting('visit-distance')->value+1;
        $distance = self::getDistanceInMeters($lat1, $lng1, $lat2, $lng2);
        $result = $distance <= $allowedDistance;
        Log::info('distance', ['distance' => $distance, 'allowedDistance' => $allowedDistance, 'result' => $result]);
        return $result;
    }
}
