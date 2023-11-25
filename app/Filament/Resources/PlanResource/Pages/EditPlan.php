<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Models\Client;
use Carbon\Carbon;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Helpers\DateHelper;
use App\Models\PlanShift;
use App\Models\Visit;
use Arr;
class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    private $visitsData = [];
    private $shiftsData = [];
    private $deletedShifts = [];
    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
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

        $visits = $this->record->visits()->with('client:id')->get();

        $fieldName = ['clients_sat','clients_sun','clients_mon','clients_tues','clients_wednes','clients_thurs','clients_fri'];
        for($i = 0; $i < 7; $i++){
            $data[$fieldName[$i]] = $visits->where('visit_date',
                Carbon::createFromDate($this->record->start_at)->addDays($i)
            )->pluck('client.id');
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $userId = auth()->id();

        $visitDates = DateHelper::calculateVisitDates();

        $fieldName = ['clients_sat','clients_sun','clients_mon','clients_tues','clients_wednes','clients_thurs','clients_fri'];

        $now = now();

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
            unset($data[$field]);
        }

        // Shifts
        $days = ['sat', 'sun', 'mon', 'tues', 'wednes', 'thurs', 'fri'];
        $this->visitsData = collect($this->visitsData);

        for ($i = 0; $i < 7; $i++) {
            if (isset($data[$fieldName[$i]])) {
                $this->shiftsData = [
                    'day' => $i + 1,
                    'am_shift' => $data[$days[$i] . '_am'],
                    'am_time' => $data[$days[$i] . '_time_am'],
                    'pm_shift' => $data[$days[$i] . '_pm'],
                    'pm_time' => $data[$days[$i] . '_time_pm'],
                ];

                $visit_date = Carbon::createFromDate($this->record->start_at)->addDays($i);
                $this->visitsData
                    ->where('visit_date', $visit_date)
                    ->whereIn('client_id', [$data[$days[$i] . '_am'], $data[$days[$i] . '_pm']])->delete();
            } else {
                $this->deletedShifts[] = $i + 1;
            }

            unset($data[$days[$i] . '_am']);
            unset($data[$days[$i] . '_time_am']);
            unset($data[$days[$i] . '_pm']);
            unset($data[$days[$i] . '_time_pm']);
        }
        $this->visitsData = $this->visitsData->toArray();


        return $data;
    }

    public function afterSave()
    {

        array_walk($this->visitsData, function (&$array) {
            $array['plan_id'] = $this->record->id;
        });

        $storedVisits = Visit::where('plan_id', $this->record->id)
            ->select(['id','client_id','visit_date'])
            ->get();

        $visitsData = collect($this->visitsData);
        $intersectedVisits = $storedVisits->intersectByKeys($visitsData);

        $visitIdsToDelete = $storedVisits->whereNotIn('id', $intersectedVisits->pluck('id'))->pluck('id');

        if (!$visitIdsToDelete->isEmpty()) {
            Visit::whereIn('id', $visitIdsToDelete)->forceDelete();
        }
dd();
        $aaa = collect($this->visitsData)->whereIn('id', Arr::pluck($this->shiftsData, ''))->delete();

        Visit::insert($this->visitsData);


        // delete unset shifts
        PlanShift::where('plan_id', $this->record->id)
            ->whereIn('day', $this->deletedShifts)
            ->delete();

        array_walk($this->shiftsData, function (&$array) {
            $array['plan_id'] = $this->record->id;
        });
        PlanShift::upsert($this->shiftsData, ['plan_id', 'day']);
    }

}
