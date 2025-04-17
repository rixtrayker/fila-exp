<?php

namespace App\Filament\Resources\SettingResource\Pages;

use App\Filament\Resources\SettingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSetting extends EditRecord
{
    protected static string $resource = SettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No delete action to prevent deletion
        ];
    }

    protected function mutateFormDataBeforeSave($data):array
    {
        if (isset($data['key'])) {
            unset($data['key']);
        }

        if (isset($data['type'])) {
            unset($data['type']);
        }

        return $data;
    }
}
