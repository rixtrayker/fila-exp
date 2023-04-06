<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Models\Plan;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPlan extends ViewRecord
{
    protected static string $resource = PlanResource::class;

    protected function getActions(): array
    {
        return [

        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $days = ['sat','sun','mon','tues','wednes','thurs','fri'];
        $record = Plan::find($data['id']);

        foreach($days as $key => $day){

            $shift = $record->shiftClient($day);
            if(!$shift) continue;

            $data[$day.'_am'] = $shift->am_shift;
            $data[$day.'_pm'] = $shift->pm_shift;
            $data[$day.'_time_am'] = $shift->am_time;
            $data[$day.'_time_pm'] = $shift->pm_time;
        }
        return $data;
    }

}
