<?php

namespace App\Services\Stats;

use App\Helpers\DateHelper;
use App\Models\Order;
use App\Traits\StatsHelperTrait;

class OrderStatsService
{
    use StatsHelperTrait;

    public function getLatestOrdersCountAvg(): float
    {
        return Order::query()
            ->where('created_at', DateHelper::today()->subDays(7))
            ->where('approved', '>', 0)
            ->count();
    }

    public function getLatestOrdersPriceAvg(): float
    {
        $ordersCount = Order::query()
            ->where('created_at', DateHelper::today()->subDays(7))
            ->where('approved', '>', 0)
            ->count();

        $ordersAmount = Order::query()
            ->where('created_at', DateHelper::today()->subDays(7))
            ->where('approved', '>', 0)
            ->sum('total');

        return $ordersCount > 0 ? $ordersAmount / $ordersCount : 0;
    }

    public function getDirectOrders(): int
    {
        return Order::query()
            ->where('created_at', DateHelper::today())
            ->where('approved', '>', 0)
            ->count();
    }

    public function getOrderStats(): array
    {
        $lastWeekPriceAvg = $this->getLatestOrdersPriceAvg();
        $priceAvg = Order::query()
            ->where('created_at', DateHelper::today())
            ->where('approved', '>', 0)
            ->avg('total');

        $lastWeekCount = $this->getLatestOrdersCountAvg();
        $ratio = $lastWeekCount > 0 ? $this->calculatePercentage($priceAvg, $lastWeekCount) : 0;

        $color = $priceAvg > $lastWeekPriceAvg ? 'success' : 'warning';
        $descriptionMessage = "last week avg: $lastWeekPriceAvg";
        $label = "$priceAvg ($ratio%)";

        return [
            'color' => $color,
            'descriptionMessage' => $descriptionMessage,
            'label' => $label
        ];
    }
}
