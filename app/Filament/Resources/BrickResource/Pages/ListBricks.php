<?php

namespace App\Filament\Resources\BrickResource\Pages;

use App\Filament\Resources\BrickResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBricks extends ListRecords
{
    protected static string $resource = BrickResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
