<?php

namespace App\Filament\Resources\VacationResource\Pages;

use App\Filament\Resources\VacationResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVacation extends CreateRecord
{
    protected static string $resource = VacationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['manager_id'] = 0;
        // if(auth()->user()->hasRole('medical-rep')){
            $data['user_id'] = auth()->id();
        // }
        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return VacationResource::getUrl('index');
    }
}
