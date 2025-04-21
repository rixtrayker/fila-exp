<?php

namespace App\Filament\Resources\ExpensesResource\Pages;

use App\Filament\Resources\ExpensesResource;
use App\Models\Setting;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExpenses extends EditRecord
{
    protected static string $resource = ExpensesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $dailyAllowance = 0;
        $kmPrice = 0;

        if(auth()->user()->hasRole('medical-rep')){
            $dailyAllowanceSetting = Setting::where('name','medical-rep-daily-allowance')->first();
            $kmPriceSetting = Setting::where('name','medical-rep-km-price')->first();

            if($dailyAllowanceSetting)
                 $dailyAllowance = $dailyAllowanceSetting->value;
            if($kmPriceSetting)
                 $kmPrice = $kmPriceSetting->value;
        }

        if(auth()->user()->hasRole('district-manager')){
            $dailyAllowanceSetting = Setting::where('name','district-manager-daily-allowance')->first();
            $kmPriceSetting = Setting::where('name','district-manager-km-price')->first();

            if($dailyAllowanceSetting)
                $data['daily_allowance'] = $dailyAllowanceSetting->value;
            if($kmPriceSetting)
                $kmPrice = $kmPriceSetting->value;
        }

        $data['daily_allowance'] = $dailyAllowance;

        $data['total'] = $data['transportation']
            + $data['accommodation']
            + ($data['distance'] * $kmPrice)
            + $data['meal']
            + $data['telephone_postage']
            + $data['daily_allowance']
            + $data['medical_expenses']
            + $data['others'];

        return $data;
    }
}
