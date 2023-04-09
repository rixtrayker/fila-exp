<?php

namespace App\Filament\Resources\ExpensesResource\Pages;

use App\Filament\Resources\ExpensesResource;
use App\Models\Setting;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateExpenses extends CreateRecord
{
    protected static string $resource = ExpensesResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // $data['user_id'] = auth()->id();

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
