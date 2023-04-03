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

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;
    protected static bool $canCreateAnother = false;
    protected $plan;
    public function create(bool $another = false): void
    {
        $this->authorizeAccess();

        try {
            $this->callHook('beforeValidate');

            $data = $this->form->getState();

            $this->callHook('afterValidate');

            $data = $this->mutateFormDataBeforeCreate($data);

            $this->callHook('beforeCreate');

            $this->saveVisits($data);
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
            return;
        }

        $this->plan = Plan::create([
            'user_id' => $userId,
            'start_at' =>  $visitDates[0],
        ]);

        foreach($data['clients_saturday'] as $itemId){
            $clientId = explode('_',$itemId)[0];
            $day = intval(explode('_',$itemId)[1]) - 1;

            $visits[] = [
                'user_id' => $userId,
                'client_id' => $clientId,
                'plan_id' => $this->plan->id,
                'visit_date' => $visitDates[$day],
            ];
        }

        Visit::insert($visits);
    }
    private function saveShifts($data){
        $days = ['sat','sun','mon','tues','wednes','thurs','fri'];
        for($i =0; $i < 7; $i++){
            if( !$this->dayHasInput($i+1) )
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

    private function dayHasInput($i){
        $all = $this->form->getRawState()['clients_saturday'];
        foreach($all as $one){
            if(explode('_',$one)[1] == $i )
                return true;
        }
        return false;
    }
}
