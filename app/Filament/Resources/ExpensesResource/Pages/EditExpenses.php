<?php

namespace App\Filament\Resources\ExpensesResource\Pages;

use App\Filament\Resources\ExpensesResource;
use App\Models\Setting;
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

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        if(auth()->user()->hasRole('medial-rep')){
            $dailyAllowance = Setting::where('name','medical-rep-daily-allowance')->first();
            if($dailyAllowance)
                $data['daily_allowance'] = $dailyAllowance->value;
        }

        if(auth()->user()->hasRole('district-manager')){
            $dailyAllowance = Setting::where('name','district-manager-daily-allowance')->first();
            if($dailyAllowance)
                $data['daily_allowance'] = $dailyAllowance->value;
        }

        return $data;
    }
}
