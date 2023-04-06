<?php

namespace App\Filament\Resources\EditRequestResource\Pages;

use App\Filament\Resources\EditRequestResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEditRequest extends EditRecord
{
    protected static string $resource = EditRequestResource::class;

    protected function getActions(): array
    {
        return [
            // Actions\ViewAction::make(),
            // Actions\DeleteAction::make(),
        ];
    }
}
