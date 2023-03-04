<?php

namespace App\Filament\Resources\CallTypeResource\Pages;

use App\Filament\Resources\CallTypeResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCallTypes extends ManageRecords
{
    protected static string $resource = CallTypeResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
