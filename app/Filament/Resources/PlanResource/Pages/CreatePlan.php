<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Helpers\DateHelper;
use App\Services\PlanDataService;
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
        $planData = PlanDataService::extract($data);
        $data['plan_data'] = $planData;
        $data['user_id'] = $userId;
        $data['start_at'] = $visitDates[0];

        return $data;
    }
}
