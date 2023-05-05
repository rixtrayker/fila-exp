<?php

namespace App\Filament\Resources\DailyVisitResource\Pages;

use App\Filament\Resources\DailyVisitResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDailyVisits extends ListRecords
{
    protected static string $resource = DailyVisitResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
