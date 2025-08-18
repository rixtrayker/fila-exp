<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use App\Models\ClientType;
use App\Helpers\DateHelper;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class DailyPlanSummaryWidget extends Widget
{
    protected static string $view = 'filament.widgets.daily-plan-summary';
    protected static ?string $minHeight = '306.5px';

    public function getColumnSpan(): int|string|array
    {
        return [
            'sm' => 1,
            'md' => 1,
            'xl' => 1,
        ];
    }

    public static function canView(): bool
    {
        $user = Auth::user();
        return $user && ($user->hasRole('medical-rep') || $user->hasRole('district-manager'));
    }

    public function getDailyPlan(): array
    {
        $today = DateHelper::today();

        // Get today's planned visits with client and brick information
        $visits = Visit::query()
            ->whereDate('visit_date', $today)
            ->where('status', 'pending')
            ->whereNotNull('plan_id')
            ->with(['client.brick', 'client.clientType'])
            ->get();

        // Group visits by brick
        $groupedByBrick = $visits->groupBy(function ($visit) {
            return $visit->client->brick_id ?? 'no_brick';
        });

        $planData = [];

        foreach ($groupedByBrick as $brickId => $brickVisits) {
            if ($brickId === 'no_brick') continue;

            $brick = $brickVisits->first()->client->brick;
            $brickName = $brick ? $brick->name : 'Unknown Brick';

            // Group by client type
            $clientsByType = [
                'AM' => [],
                'PM' => [],
                'PH' => []
            ];

            foreach ($brickVisits as $visit) {
                $client = $visit->client;
                $clientType = $this->determineClientDisplayType($client);

                $clientsByType[$clientType][] = [
                    'id' => $client->id,
                    'name' => $client->name_en,
                    'visit_id' => $visit->id,
                    'status' => $visit->status
                ];
            }

            // Only include bricks that have clients
            $totalClients = count($clientsByType['AM']) + count($clientsByType['PM']) + count($clientsByType['PH']);
            if ($totalClients > 0) {
                $planData[] = [
                    'brick_id' => $brickId,
                    'brick_name' => $brickName,
                    'clients' => $clientsByType,
                    'total_clients' => $totalClients
                ];
            }
        }

        // Sort by brick name
        usort($planData, function ($a, $b) {
            return strcmp($a['brick_name'], $b['brick_name']);
        });

        return $planData;
    }

    private function determineClientDisplayType($client): string
    {
        if (!$client->clientType) {
            return 'AM'; // Default fallback
        }

        $typeName = $client->clientType->name;

        // Check for pharmacy types first (most specific)
        if (stripos($typeName, 'pharmacy') !== false || stripos($typeName, 'ph') !== false) {
            return 'PH';
        }
        
        // Check for PM types (clinics)
        if (stripos($typeName, 'clinic') !== false || stripos($typeName, 'poly clinic') !== false) {
            return 'PM';
        }
        
        // Check for AM types (hospitals and medical centers)
        if (stripos($typeName, 'hospital') !== false || 
            stripos($typeName, 'resuscitation') !== false || 
            stripos($typeName, 'incubators') !== false ||
            stripos($typeName, 'medical center') !== false ||
            stripos($typeName, 'medical centre') !== false) {
            return 'AM';
        }

        // Default fallback - check client grade if available
        if (isset($client->grade)) {
            if ($client->grade === 'PH') {
                return 'PH';
            }
        }

        return 'AM'; // Final fallback
    }

    public function getTotalClients(): int
    {
        $planData = $this->getDailyPlan();
        return array_sum(array_column($planData, 'total_clients'));
    }

    public function getTotalBricks(): int
    {
        return count($this->getDailyPlan());
    }

    public function getMinHeight(): ?string
    {
        return static::$minHeight;
    }
}
