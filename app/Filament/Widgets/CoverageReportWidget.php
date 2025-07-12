<?php

namespace App\Filament\Widgets;

use App\Services\Stats\CoverageStatsService;
use App\Services\VisitCacheService;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class CoverageReportWidget extends Widget
{
    protected static string $view = 'filament.widgets.coverage-report-widget';
    public ?string $selectedType = 'AM';

    public function mount(): void
    {
        $this->selectedType = 'AM';
    }

    public function getColumnSpan(): int|string|array
    {
        return [
            'sm' => 1,
            'md' => 1,
            'xl' => 1,
        ];
    }

    public function getChartData(): array
    {
        $userId = auth()->id();
        $date = now()->format('Y-m-d');
        $type = strtolower($this->selectedType);

        $cacheType = match($type) {
            'am' => VisitCacheService::TYPE_COVERAGE_AM,
            'pm' => VisitCacheService::TYPE_COVERAGE_PM,
            'pharmacy' => VisitCacheService::TYPE_COVERAGE_PHARMACY,
            default => VisitCacheService::TYPE_COVERAGE_AM,
        };

        return VisitCacheService::getCachedCoverageData($userId, $date, $cacheType, function () {
            return $this->generateChartData();
        });
    }

    protected function generateChartData(): array
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        $type = strtolower($this->selectedType);

        $data = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $visitData = CoverageStatsService::getVisitData($currentDate, $type);
            $data[] = [
                'date' => $currentDate->format('M d'),
                'visits' => $visitData['total'],
                'completed' => $visitData['data'][0], // Visited
                'pending' => $visitData['data'][1],   // Pending
                'cancelled' => $visitData['data'][2], // Missed
            ];
            $currentDate->addDay();
        }

        return $data;
    }

    public function getStats(): array
    {
        $data = $this->getChartData();

        $totalVisits = collect($data)->sum('visits');
        $completedVisits = collect($data)->sum('completed');
        $pendingVisits = collect($data)->sum('pending');
        $cancelledVisits = collect($data)->sum('cancelled');

        $completionRate = $totalVisits > 0 ? round(($completedVisits / $totalVisits) * 100, 1) : 0;

        return [
            'total' => $totalVisits,
            'completed' => $completedVisits,
            'pending' => $pendingVisits,
            'cancelled' => $cancelledVisits,
            'completion_rate' => $completionRate,
        ];
    }

    public function updatedSelectedType(): void
    {
        $userId = auth()->id();
        $date = now()->format('Y-m-d');
        app(VisitCacheService::class)->clearCacheForUserAndDate($userId, $date);
    }
}
