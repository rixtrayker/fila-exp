<?php

namespace App\Filament\Resources\DailyVisitResource\Pages;

use App\Filament\Resources\DailyVisitResource;
use App\Helpers\DateHelper;
use App\Models\Feature;
use App\Models\Visit;
use App\Services\LocationService;
use App\Services\VisitService;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Exceptions\Halt;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class EditDailyVisit extends EditRecord
{
    protected static string $resource = DailyVisitResource::class;

    protected LocationService $locationService;
    protected VisitService $visitService;

    public function __construct()
    {
        $this->locationService = app(LocationService::class);
        $this->visitService = app(VisitService::class);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->validateLocation($data);
        $data['status'] = 'visited';
        return $data;
    }

    protected function validateLocation(array $data): void
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

        $this->record->lat = $location->get('latitude');
        $this->record->lng = $location->get('longitude');
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

    public function afterSave(): void
    {
        try {
            $this->visitService->saveProducts($this->record, $this->form->getRawState());
        } catch (\Exception $e) {
            Log::error('Error saving products: ' . $e->getMessage());
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
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
