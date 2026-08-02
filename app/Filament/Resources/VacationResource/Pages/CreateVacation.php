<?php

namespace App\Filament\Resources\VacationResource\Pages;

use App\Filament\Resources\VacationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVacation extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = VacationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // The form has no rep picker, so a request always belongs to its author.
        $data['user_id'] = auth()->id();

        // Approval state is owned by the approve/reject actions, never by the
        // submitted payload.
        unset($data['approved'], $data['approved_at']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return VacationResource::getUrl('index');
    }
}
