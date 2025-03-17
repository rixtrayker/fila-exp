<?php

namespace App\Helpers;

class LocationHelpers
{
    public static function calculateDistance($latitude1, $longitude1, $latitude2, $longitude2)
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

    public static function isValidDistance($lat1, $lng1, $lat2, $lng2)
    {
        $distance = self::getDistanceInMeters($lat1, $lng1, $lat2, $lng2);
        return $distance <= 300;
    }
}
