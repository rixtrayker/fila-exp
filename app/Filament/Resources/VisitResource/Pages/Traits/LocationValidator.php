<?php

namespace App\Filament\Resources\VisitResource\Pages\Traits;

use App\Models\Feature;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;

trait LocationValidator
{
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

    protected function sendLocationError(string $message): void
    {
        Notification::make()
            ->title('Error')
            ->body($message)
            ->danger()
            ->send();
        throw new Halt();
    }
}
