<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use App\Services\PlanDataService;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

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
        $planData = PlanDataService::normalize($this->record->plan_data);
        unset($data['plan_data']);
        $data = array_merge($data, $planData);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $planData = PlanDataService::extract($data);
        $data['plan_data'] = $planData;

        return $data;
    }
}
