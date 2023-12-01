<?php

namespace App\Filament\Resources\EditRequestResource\Pages;

use App\Filament\Resources\EditRequestResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEditRequests extends ListRecords
{
    protected static string $resource = EditRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}
