<?php

namespace App\Filament\Resources\VacationResource\Pages;

use App\Filament\Resources\VacationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVacation extends EditRecord
{
    protected static string $resource = VacationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Approval state is owned by the approve/reject actions, and the owning
        // rep never changes on edit.
        unset($data['approved'], $data['approved_at'], $data['user_id']);

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return VacationResource::getUrl('index');
    }
}
