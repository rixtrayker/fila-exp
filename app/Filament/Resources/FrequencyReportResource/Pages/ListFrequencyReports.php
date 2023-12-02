<?php

namespace App\Filament\Resources\FrequencyReportResource\Pages;

use App\Filament\Resources\FrequencyReportResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Model;

class ListFrequencyReports extends ListRecords
{
    protected static string $resource = FrequencyReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }

    public function getTableRecordKey(Model $model):string
    {
        return 'id';
    }
}
