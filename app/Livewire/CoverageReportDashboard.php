<?php

namespace App\Livewire;

use App\Services\Stats\CoverageStatsService;
use Livewire\Component;

class CoverageReportDashboard extends Component
{
    public string $selectedType = 'am';
    public array $chartData = [];

    public function mount()
    {
        $this->loadChartData();
    }

    public function loadChartData()
    {
        $this->chartData = [
            'am' => CoverageStatsService::getCoverageData('am'),
            'pm' => CoverageStatsService::getCoverageData('pm'),
            'pharmacy' => CoverageStatsService::getCoverageData('pharmacy'),
        ];
    }

    public function switchType($type)
    {
        $this->selectedType = $type;
        $this->dispatch('switchChartType', type: $type);
    }

    public function render()
    {
        return view('livewire.coverage-report-dashboard');
    }
}
