<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Helpers\DateHelper;
use App\Models\Plan;
use App\Models\PlanShift;
use App\Models\Visit;
use Carbon\Carbon;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Str;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;
    protected static bool $canCreateAnother = false;
    protected $plan;
    private $creationStatus;
    public function create(bool $another = false): void
    {
        $this->authorizeAccess();

        try {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeCreate($data);

            $this->callHook('beforeCreate');

            $this->creationStatus = $this->saveVisits($data);
            if($this->creationStatus  == -1){
                $this->getCreatedNotification()?->icon('heroicon-s-exclamation')->iconColor('warning')->send();
                $this->redirect(PlanResource::getUrl('index'));
                return;
            }
            $this->saveShifts($data);

            $this->callHook('afterCreate');
        } catch (Halt $exception) {
            return;
        }

        $this->getCreatedNotification()?->send();

        if ($another) {
            // Ensure that the form record is anonymized so that relationships aren't loaded.
            $this->form->model($this->record::class);
            $this->record = null;

            $this->fillForm();

            return;
        }

        $this->redirect(PlanResource::getUrl('index'));
    }

    public function saveVisits($data)
    {
        $visits = [];
        $userId = auth()->id();

        $visitDates = DateHelper::calculateVisitDates();

        $existedPlan = Plan::where([
            'user_id' => $userId,
            'start_at' => $visitDates[0],
        ])->first();

        if ($existedPlan){
            $this->plan = $existedPlan;
            return -1;
        }

        $this->plan = Plan::create([
            'user_id' => $userId,
            'start_at' =>  $visitDates[0],
        ]);

        $fieldName = ['clients_sat','clients_sun','clients_mon','clients_tues','clients_wednes','clients_thurs','clients_fri'];

        $now = now();

        foreach($fieldName as $dayKey => $field){
            if(!isset($data[$field]))
                continue;

            foreach($data[$field] as $clientId){
                $visits[] = [
                    'user_id' => $userId,
                    'client_id' => $clientId,
                    'plan_id' => $this->plan->id,
                    'visit_date' => $visitDates[$dayKey],
                    'visit_type_id' => 1,
                    'status' => 'planned',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        Visit::insert($visits);
        return 1;
    }

    public function saveShifts($data)
    {
        $fieldName = ['clients_sat','clients_sun','clients_mon','clients_tues','clients_wednes','clients_thurs','clients_fri'];
        $days = ['sat','sun','mon','tues','wednes','thurs','fri'];

        for($i =0; $i < 7; $i++){

            if(!isset($data[$fieldName[$i]]))
                continue;

            PlanShift::updateOrCreate([
                'plan_id' => $this->plan->id,
                'day' => $i+1,
                'am_shift' => $data[$days[$i].'_am'],
                'am_time' => $data[$days[$i].'_time_am'],
                'pm_shift' => $data[$days[$i].'_pm'],
                'pm_time' => $data[$days[$i].'_time_pm'],
            ]);
        }
    }
    protected function getCreatedNotificationMessage(): ?string
    {
        if($this->creationStatus === 1)
        {
            return __('filament::resources/pages/create-record.messages.created');
        } else if($this->creationStatus === -1){
            return 'Plan already created';
        }
        return '';
    }

}
