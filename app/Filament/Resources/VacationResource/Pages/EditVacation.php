<?php

namespace App\Filament\Resources\VacationResource\Pages;

use App\Filament\Resources\VacationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVacation extends EditRecord
{
    protected static string $resource = VacationResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return VacationResource::getUrl('index');
    }
}
