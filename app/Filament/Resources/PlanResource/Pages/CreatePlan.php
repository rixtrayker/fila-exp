<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Helpers\DateHelper;
use App\Models\Plan;
use App\Models\PlanShift;
use App\Models\Visit;
use Filament\Pages\Actions;
// use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePlan extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = PlanResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $userId = auth()->id();

        $visitDates = DateHelper::calculateVisitDates();
        $days = ['sat', 'sun', 'mon', 'tues', 'wednes', 'thurs', 'fri'];

        $planData = [];

        for($i = 0; $i < 7; $i++){
            $field = $days[$i] . '_clients';
            if(!isset($data[$field]))
                continue;

            $planData[$field] = array_values(array_map(fn($val) => intval($val),$data[$field]));
            unset($data[$field]);

            $inputs = ['_am_shift','_pm_shift','_am_time','_pm_time'];
            for ($j = 0; $j < 4; $j++) {
                $key = $days[$i] . $inputs[$j];
                if(isset($data[$key]))
                    $planData[$key] = intval($data[$key]);
                unset($data[$key]);
            }
        }

        $planData = array_filter($planData);
        $data['plan_data'] = $planData;
        $data['user_id'] = $userId;
        $data['start_at'] = $visitDates[0];
        return $data;
    }
}
