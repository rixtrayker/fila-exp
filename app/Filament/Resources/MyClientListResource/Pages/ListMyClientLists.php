<?php

namespace App\Filament\Resources\MyClientListResource\Pages;

use App\Filament\Resources\MyClientListResource;
use Filament\Resources\Pages\ListRecords;

class ListMyClientLists extends ListRecords
{
    protected static string $resource = MyClientListResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }

    public function isTableSearchable(): bool
    {
        return true;
    }
}
