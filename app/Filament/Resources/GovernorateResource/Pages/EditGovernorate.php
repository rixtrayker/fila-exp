<?php

namespace App\Filament\Resources\GovernorateResource\Pages;

use App\Filament\Resources\GovernorateResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGovernorate extends EditRecord
{
    protected static string $resource = GovernorateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
