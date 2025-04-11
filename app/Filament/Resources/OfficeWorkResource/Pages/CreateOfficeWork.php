<?php

namespace App\Filament\Resources\OfficeWorkResource\Pages;

use App\Filament\Resources\OfficeWorkResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOfficeWork extends CreateRecord
{
    protected static string $resource = OfficeWorkResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['user_id'] = auth()->id();
        return $data;
    }
}
