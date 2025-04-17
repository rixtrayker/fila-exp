<?php

namespace App\Filament\Resources\VisitResource\Pages;

use App\Filament\Resources\VisitResource;
use App\Models\Feature;
use App\Models\Visit;
use App\Services\LocationService;
use App\Services\VisitService;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

class CreateVisit extends CreateRecord
{
    protected static string $view = 'vendor.filament.pages.create-visit';
    protected static string $resource = VisitResource::class;

    protected LocationService $locationService;
    protected VisitService $visitService;

    public function __construct()
    {
        $this->locationService = app(LocationService::class);
        $this->visitService = app(VisitService::class);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->validateLocation($data);
        $this->setUserData($data);

        return $data;
    }

    protected function validateLocation(array &$data): void
    {
        $featureEnabled = Feature::isEnabled('location');

        if (!$featureEnabled) {
            return;
        }

        $location = $this->locationService->getLocation($this->getId());

        if (!$location) {
            $this->sendLocationError('Location service is not enabled');
        }

        if (!$this->locationService->validateVisitLocation($data['client_id'], $location)) {
            $this->sendLocationError('Location is too far from the client');
        }

        $data['lat'] = $location->get('latitude');
        $data['lng'] = $location->get('longitude');
    }

    protected function setUserData(array &$data): void
    {
        $isMedicalRep = $this->isMedicalRep();

        if ($isMedicalRep) {
            $data['user_id'] = Auth::id();
            $data['visit_date'] = today();
        }

        $data['status'] = 'visited';
    }

    protected function isMedicalRep(): bool
    {
        return auth()->user()?->hasRole('medical-rep');
    }

    protected function sendLocationError(string $message): void
    {
        Notification::make()
            ->title('Error')
            ->body($message)
            ->danger()
            ->send();
        throw new Halt();
    }

    public function create(bool $another = false): void
    {
        $this->authorizeAccess();

        try {
            $this->callHook('beforeValidate');
            $data = $this->form->getState();
            $this->callHook('afterValidate');
            $data = $this->mutateFormDataBeforeCreate($data);

            $visit = $this->visitService->findExistingVisit($data);

            if ($visit) {
                $this->handleExistingVisit($visit, $data);
                return;
            }

            $this->createNewVisit($data);
        } catch (Halt $exception) {
            return;
        }

        $this->getCreatedNotification()?->send();

        if ($another) {
            $this->resetForm();
            return;
        }

        $this->redirect($this->getRedirectUrl());
    }

    protected function handleExistingVisit(Visit $visit, array $data): void
    {
        $this->record = $visit;
        $this->visitService->updateExistingVisit($visit, $data);

        if ($visit->status === 'visited') {
            $this->getCreatedNotification()?->send();
            $this->redirect($this->getRedirectUrl());
            return;
        }

        $this->visitService->saveProducts($visit, $this->form->getRawState());
        $this->getCreatedNotification()?->send();
        $this->redirect($this->getRedirectUrl());
    }

    protected function createNewVisit(array $data): void
    {
        $this->callHook('beforeCreate');
        $data['status'] = 'visited';
        $this->record = $this->handleRecordCreation($data);
        $this->callHook('afterCreate');
    }

    protected function resetForm(): void
    {
        $this->form->model($this->record::class);
        $this->record = null;
        $this->fillForm();
    }

    protected $listeners = ['location-fetched' => 'updateLocation'];

    public function updateLocation($data): void
    {
        $data = collect($data);
        if ($data->has('latitude') && $data->has('longitude')) {
            $this->locationService->setLocation($this->getId(), $data);
        }
    }
}
