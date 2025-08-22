<?php

namespace App\Filament\Resources\SOPsAndCallRateResource\Pages;

use App\Filament\Resources\SOPsAndCallRateResource;
use App\Models\SOPsAndCallRate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSOPsAndCallRates extends ListRecords
{
    protected static string $resource = SOPsAndCallRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // No actions needed for read-only reports
        ];
    }

    /**
     * Override to handle stored procedure results instead of database queries
     */
    public function getTableRecords(): \Illuminate\Database\Eloquent\Collection|\Illuminate\Contracts\Pagination\Paginator|\Illuminate\Contracts\Pagination\CursorPaginator
    {
        try {
            $filtersState = $this->getTableFiltersForm()?->getState() ?? [];

            // Get data directly from stored procedure
            $data = SOPsAndCallRate::getReportDataWithFilters($filtersState);

            if (empty($data)) {
                return new \Illuminate\Database\Eloquent\Collection([]);
            }

            // Convert to Eloquent collection of models for Filament
            $models = collect($data)->map(function ($item) {
                $model = new SOPsAndCallRate();
                // Ensure all fillable fields are properly set
                $fillableData = [];
                foreach ($model->getFillable() as $field) {
                    $fillableData[$field] = $item->{$field} ?? null;
                }
                $model->forceFill($fillableData);
                // Set the primary key if it exists
                if (isset($item->id)) {
                    $model->setKey($item->id);
                }
                return $model;
            });

            return new \Illuminate\Database\Eloquent\Collection($models->toArray());
        } catch (\Exception $e) {
            // Log error and return empty collection
            Log::error('Error in SOPsAndCallRate getTableRecords: ' . $e->getMessage());
            return new \Illuminate\Database\Eloquent\Collection([]);
        }
    }
}
