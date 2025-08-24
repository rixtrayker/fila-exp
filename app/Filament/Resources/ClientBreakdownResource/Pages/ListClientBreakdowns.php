<?php

namespace App\Filament\Resources\ClientBreakdownResource\Pages;

use App\Filament\Resources\ClientBreakdownResource;
use Filament\Resources\Pages\ListRecords;

class ListClientBreakdowns extends ListRecords
{
    protected static string $resource = ClientBreakdownResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
