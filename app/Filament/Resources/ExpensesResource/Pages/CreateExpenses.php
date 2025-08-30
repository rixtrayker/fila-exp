<?php

namespace App\Filament\Resources\ExpensesResource\Pages;

use App\Filament\Resources\ExpensesResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateExpenses extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = ExpensesResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // No complex calculations needed for simplified expense structure
        return $data;
    }
}
