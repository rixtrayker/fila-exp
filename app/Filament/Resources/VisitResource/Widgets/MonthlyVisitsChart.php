<?php

namespace App\Filament\Resources\VisitResource\Widgets;

use Filament\Widgets\PieChartWidget;

class MonthlyVisitsChart extends PieChartWidget
{
    protected static ?string $maxHeight = '300px';

    protected function getHeading(): string
    {
        return 'Monthly Visits';
    }
    protected function getData(): array
    {
        return [
            'datasets' => [
                [
                    'label' => 'Jan',
                    'data' => 10,
                ],
            ],
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        ];
    }
}
