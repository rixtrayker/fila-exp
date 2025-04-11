<?php

namespace App\Filament\Resources\PlanResource;

use App\Models\Client;
use App\Models\ClientType;
use App\Models\Visit;
use App\Helpers\DateHelper;
use Carbon\Carbon;
use Illuminate\Support\Str;
class ClientManager
{
    // Client cache management
    private static bool $isPrepared = false;
    private static array $allClients = [];
    private static array $clients = [];
    private static array $pmClients = [];
    private static array $pharmacyClients = [];

    /**
     * Get clients by type from the cache
     */
    public static function getClients($type = null, $day = null): array
    {
        if (!self::$isPrepared) {
            self::prepareData();
        }

        $key = $type ?? 'all';
        if (auth()->user()->hasRole('district_manager')) {
            $key = $day. '-' . $type;
        }

        return self::$clients[$key] ?? [];
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

        self::$clients['all'] = $allClients->pluck('name_en', 'id')->toArray();
        self::$clients['am'] = $allClients->whereIn('client_type_id', $amTypeIDs)->pluck('name_en', 'id')->toArray();
        self::$clients['pm'] = $allClients->whereIn('client_type_id', $pmTypeIDs)->pluck('name_en', 'id')->toArray();
        self::$clients['pharmacy'] = $allClients->whereIn('client_type_id', $pharmacyTypeIDs)->pluck('name_en', 'id')->toArray();

        $dates = DateHelper::calculateVisitDates();
        $days = array_map(function($date) {
            return Str::lower(Carbon::parse($date)->format('D'));
        }, $dates);

        $plannedClients = Visit::districtManagerClients();
        if (auth()->user()->hasRole('district_manager')) {
            foreach ($dates as $i => $date) {
                $day = $days[$i];
                $clients = $plannedClients->where('visit_date', $date)->pluck('client_id', 'id')->toArray();
                self::$clients[$day] = $clients;
                self::$clients[$day.'-am'] = $allClients->whereIn('client_type_id', $amTypeIDs)->pluck('name_en', 'id')->toArray();
                self::$clients[$day.'-pm'] = $allClients->whereIn('client_type_id', $pmTypeIDs)->pluck('name_en', 'id')->toArray();
                self::$clients[$day.'-pharmacy'] = $allClients->whereIn('client_type_id', $pharmacyTypeIDs)->pluck('name_en', 'id')->toArray();
            }
        }

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