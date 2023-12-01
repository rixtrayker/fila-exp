<?php

namespace App\Filament\Resources\VisitResource\Widgets;

use Filament\Widgets\ChartWidget;

class MonthlyVisitsChart extends ChartWidget
{
    protected static ?string $maxHeight = '300px';


    protected function getType(): string
    {
        return 'pie';
    }

    public function getHeading(): string
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
