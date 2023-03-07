<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Helpers\DateHelper;
use App\Models\Plan;
use App\Models\Visit;
use Carbon\Carbon;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;
    protected static bool $canCreateAnother = false;
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

    public function getVisitDate($day)
    {
        $nextWeek = today()->dayOfWeek == Carbon::FRIDAY;
        $date = new Carbon(DateHelper::getFirstOfWeek($nextWeek));
        return $date->addDays($day);
    }

    public function calculateVisitDates()
    {
        $dates = [];

        for($i = 0; $i < 7; $i++)
            $dates[] = $this->getVisitDate($i);

        return $dates;
    }

    public function saveVisits($data)
    {
        $visits = [];
        $userId = auth()->user()->id;

        $startAt = $this->getVisitDate(1);
        $visitDates = $this->calculateVisitDates();

        $existedPlan = Plan::where([
            'user_id' => $userId,
            'start_at' => $startAt,
        ])->first();

        if ($existedPlan){
            return;
        }

        $plan = Plan::create([
            'user_id' => $userId,
            'start_at' =>  $startAt,
        ]);

        foreach($data['clients_saturday'] as $itemId){
            $clientId = explode('_',$itemId)[0];
            $day = intval(explode('_',$itemId)[1]) - 1;

            $visits[] = [
                'user_id' => $userId,
                'client_id' => $clientId,
                'plan_id' => $plan->id,
                'visit_date' => $visitDates[$day],
            ];
        }

        Visit::insert($visits);
    }
}
