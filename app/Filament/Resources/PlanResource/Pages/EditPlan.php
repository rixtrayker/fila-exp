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

class EditPlan extends EditRecord
{
    protected static string $resource = PlanResource::class;

    private $visitsData = [];
    private $shiftsData = [];
    private $deletedShifts = [];
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $planData = $this->record->plan_data;
        unset($data['plan_data']);
        $data = array_merge($data,$planData);
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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
        return $data;
    }
}
