<?php

namespace App\Traits;

trait StatsHelperTrait
{
    protected function getTrendIcon(float $lastDaysRatio, float $currentDayRatio): string
    {
        return $currentDayRatio >= $lastDaysRatio
            ? 'heroicon-m-arrow-trending-up'
            : 'heroicon-m-arrow-trending-down';
    }

    protected function getStatsColor(float $ratio): string
    {
        if ($ratio >= 80) {
            return 'success';
        }

        if ($ratio >= 50) {
            return 'warning';
        }

        return 'danger';
    }

    protected function calculatePercentage(int $value, int $total): float
    {
        if ($total === 0) {
            return 0;
        }

        return round(($value / $total) * 100, 2);
    }
}
