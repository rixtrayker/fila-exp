<?php

namespace App\Filament\Resources\ExpensesResource\Pages;

use App\Filament\Resources\ExpensesResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExpenses extends EditRecord
{
    protected static string $resource = ExpensesResource::class;

    protected function getActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
