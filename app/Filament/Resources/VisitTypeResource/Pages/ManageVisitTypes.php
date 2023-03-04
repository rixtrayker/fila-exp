<?php

namespace App\Filament\Resources\VisitTypeResource\Pages;

use App\Filament\Resources\VisitTypeResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageVisitTypes extends ManageRecords
{
    protected static string $resource = VisitTypeResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
