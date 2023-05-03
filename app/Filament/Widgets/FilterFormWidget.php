<?php

namespace App\Filament\Widgets;

use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Form;
use Filament\Tables\Filters\Concerns\InteractsWithTableQuery;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\TemporaryUploadedFile;

class FilterFormWidget extends Widget implements HasForms
{
    use InteractsWithForms;
    protected static string $view = 'filament.widgets.filter-form-widget';
    public $from;
    public $to;
    public $ids = [];
    public $query = [];


    public function queryString(){

            return [
                'from' => ['except' => ''],
                'to' => ['except' => ''],
                'ids' => ['except' => ''],
            ];
    }
    protected function getFormSchema()
    {

        return [
            // visite
        ];
    }



    // protected function getViewData(): array
    // {
    //     return [
    //         //
    //     ];
    // }

}
