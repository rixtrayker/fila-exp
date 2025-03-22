<?php

namespace App\Filament\Resources\PlanResource;

use App\Models\Client;
use App\Models\ClientType;

class ClientManager
{
    // Client cache management
    private static bool $isPrepared = false;
    private static array $allClients = [];
    private static array $amClients = [];
    private static array $pmClients = [];
    private static array $pharmacyClients = [];

    /**
     * Get clients by type from the cache
     */
    public static function getClients($type = null): array
    {
        if (!self::$isPrepared) {
            self::prepareData();
        }

        return match($type) {
            'am' => self::$amClients,
            'pm' => self::$pmClients,
            'pharmacy' => self::$pharmacyClients,
            'all' => self::$allClients,
            default => self::$allClients
        };
    }

    /**
     * Load and cache client data
     */
    private static function prepareData(): void
    {
        $allClients = Client::inMyAreas()->select('client_type_id', 'name_en', 'id')->get();
        $clientTypes = ClientType::all();

        // Define client type mappings
        $amTypeIDs = $clientTypes->whereIn('name', [
            'Hospital',
            'Resuscitation Centre',
            'Incubators Centre'
        ])->pluck('id')->toArray();

        $pmTypeIDs = $clientTypes->whereIn('name', [
            'doctor',
            'clinic',
            'polyclinic'
        ])->pluck('id')->toArray();

        $pharmacyTypeIDs = $clientTypes->whereIn('name', [
            'pharmacy'
        ])->pluck('id')->toArray();

        // Cache client lists
        self::$allClients = $allClients->pluck('name_en', 'id')->toArray();
        self::$amClients = $allClients->whereIn('client_type_id', $amTypeIDs)->pluck('name_en', 'id')->toArray();
        self::$pmClients = $allClients->whereIn('client_type_id', $pmTypeIDs)->pluck('name_en', 'id')->toArray();
        self::$pharmacyClients = $allClients->whereIn('client_type_id', $pharmacyTypeIDs)->pluck('name_en', 'id')->toArray();

        self::$isPrepared = true;
    }

    /**
     * Search clients by name
     */
    public static function searchClients(string $search)
    {
        return Client::inMyAreas()
            ->where('name_en', 'like', "%{$search}%")
            ->orWhere('name_ar', 'like', "%{$search}%")
            ->limit(50)
            ->pluck('name_en', 'id');
    }
}