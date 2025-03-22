<?php

namespace App\Filament\Resources\OfficeWorkResource\Pages;

use App\Filament\Resources\OfficeWorkResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOfficeWorks extends ListRecords
{
    protected static string $resource = OfficeWorkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
