<?php

namespace App\Filament\Resources\DailyVisitResource\Pages;

use App\Filament\Resources\DailyVisitResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyVisit extends EditRecord
{
    protected static string $resource = DailyVisitResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
