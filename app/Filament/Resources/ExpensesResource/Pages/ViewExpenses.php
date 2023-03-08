<?php

namespace App\Filament\Resources\ExpensesResource\Pages;

use App\Filament\Resources\ExpensesResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewExpenses extends ViewRecord
{
    protected static string $resource = ExpensesResource::class;

    protected function getActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
