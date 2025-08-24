<?php

namespace App\Filament\Widgets;

use App\Models\Visit;
use App\Models\ClientType;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class MonthlyVisitStatsWidget extends Widget
{
    protected static string $view = 'filament.widgets.monthly-visit-stats-widget';
    public ?string $selectedType = 'PM'; // Changed default to PM

    public function mount(): void
    {
        $this->selectedType = 'PM'; // Changed default to PM
    }

    public function getColumnSpan(): int|string|array
    {
        return [
            'sm' => 1,
            'md' => 1,
            'xl' => 1,
        ];
    }

    public function getStats(): array
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        $type = strtolower($this->selectedType);

        // Get all visits for the month in one query
        $visits = $this->getVisitsForMonth($startDate, $endDate, $type);

        $totalVisits = $visits->count();
        $completedVisits = $visits->where('status', 'visited')->count();
        $pendingVisits = $visits->where('status', 'pending')->count();
        $cancelledVisits = $visits->where('status', 'cancelled')->count();

        $completionRate = $totalVisits > 0 ? round(($completedVisits / $totalVisits) * 100, 1) : 0;

        return [
            'total' => $totalVisits,
            'completed' => $completedVisits,
            'pending' => $pendingVisits,
            'cancelled' => $cancelledVisits,
            'completion_rate' => $completionRate,
        ];
    }

    protected function getVisitsForMonth(Carbon $startDate, Carbon $endDate, string $type)
    {
        $query = Visit::query()
            ->whereBetween('visit_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->with('client'); // Eager load client relationship

        // Filter by client type based on the selected type
        switch ($type) {
            case 'am':
                $query->whereHas('client', function ($q) {
                    $q->where('client_type_id', ClientType::AM);
                });
                break;
            case 'pm':
                $query->whereHas('client', function ($q) {
                    $q->where('client_type_id', ClientType::PM);
                });
                break;
            case 'pharmacy':
                $query->whereHas('client', function ($q) {
                    $q->where('client_type_id', ClientType::PH);
                });
                break;
            default:
                // If no valid type, get all visits
                break;
        }

        return $query->get();
    }

    public function updatedSelectedType(): void
    {
        // No caching to clear
    }
}
