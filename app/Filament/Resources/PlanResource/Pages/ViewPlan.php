<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Models\Plan;
use Carbon\Carbon;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewPlan extends ViewRecord
{
    protected static string $resource = PlanResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $days = ['sat','sun','mon','tues','wednes','thurs','fri'];
        $record = $this->record;

        foreach($days as $key => $day){

            $shift = $record->shiftClient($day);
            if(!$shift) continue;

            $data[$day.'_am'] = $shift->am_shift;
            $data[$day.'_pm'] = $shift->pm_shift;
            $data[$day.'_time_am'] = $shift->am_time;
            $data[$day.'_time_pm'] = $shift->pm_time;
        }

        $visits = $record->visits()->with('client:id')->get();

        $fieldName = ['clients_sat','clients_sun','clients_mon','clients_tues','clients_wednes','clients_thurs','clients_fri'];
        for($i = 0; $i < 7; $i++){
            $data[$fieldName[$i]] = $visits->where('visit_date',
                Carbon::createFromDate($record->start_at)->addDays($i)
            )->pluck('client.id');
        }

        return $data;
    }

}
