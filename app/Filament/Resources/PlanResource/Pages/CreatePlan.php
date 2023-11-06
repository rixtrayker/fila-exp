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
    protected static string $resource = PlanResource::class;
    protected static bool $canCreateAnother = false;
    private $visitsData = [];
    private $shiftsData = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $userId = auth()->id();

        $visitDates = DateHelper::calculateVisitDates();

        $fieldName = ['clients_sat','clients_sun','clients_mon','clients_tues','clients_wednes','clients_thurs','clients_fri'];

        $now = now();
        $filledDates = [];

        foreach($fieldName as $dayKey => $field){
            if(!isset($data[$field]))
                continue;

            foreach($data[$field] as $clientId){
                $this->visitsData[] = [
                    'user_id' => $userId,
                    'client_id' => $clientId,
                    'visit_date' => $visitDates[$dayKey],
                    'visit_type_id' => 1,
                    'status' => 'planned',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            $filledDates[] = $dayKey;
            unset($data[$field]);
        }

        // Shifts
        $days = ['sat', 'sun', 'mon', 'tues', 'wednes', 'thurs', 'fri'];

        for ($i = 0; $i < 7; $i++) {
            if (in_array($i, $filledDates)) {
                $this->shiftsData[] = [
                    'day' => $i + 1,
                    'am_shift' => $data[$days[$i] . '_am'],
                    'am_time' => $data[$days[$i] . '_time_am'],
                    'pm_shift' => $data[$days[$i] . '_pm'],
                    'pm_time' => $data[$days[$i] . '_time_pm'],
                ];
            }

            unset($data[$days[$i] . '_am']);
            unset($data[$days[$i] . '_time_am']);
            unset($data[$days[$i] . '_pm']);
            unset($data[$days[$i] . '_time_pm']);
        }


        $data['user_id'] = $userId;
        $data['start_at'] = $visitDates[0];
        return $data;
    }

    public function afterCreate()
    {

        array_walk($this->visitsData, function (&$array) {
            $array['plan_id'] = $this->record->id;
        });
        Visit::insert($this->visitsData);

        array_walk($this->shiftsData, function (&$array) {
            $array['plan_id'] = $this->record->id;
        });
        PlanShift::upsert($this->shiftsData, ['plan_id', 'day']);
    }

}
