<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Forms\Components\Actions\Modal\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Actions\ViewAction;
use Filament\Pages\Contracts\HasFormActions;
use Filament\Resources\Form;
use Filament\Widgets\Widget;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\TemporaryUploadedFile;
use Str;
class FilterFormWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.filter-form-widget';
    public $from;
    public $to;
    public $user_id = [];
    public $query = [];

    public function queryString(){
        return [
            // 'from' => ['except' => ''],
            // 'to' => ['except' => ''],
            // 'user_id' => ['except' => ''],
        ];
    }
    protected function getFormSchema()
    {
        return [
            Select::make('user_id')
                ->label('Medical Rep')
                ->searchable()
                ->multiple()
                ->placeholder('Search name')
                // ->getSearchResultsUsing(fn (string $search) => User::role('medical-rep')->mine()->where('name', 'like', "%{$search}%")->limit(50)->pluck('name', 'id'))
                ->options(User::mine()->pluck('name', 'id'))
                // ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
                ->preload(),
            DatePicker::make('from')
                ->displayFormat('Y-m-d')
                ->label('From Date')
                ->default(today())
                ->closeOnDateSelection()
                ->reactive()
                ->maxDate(today()),
            DatePicker::make('to')
                ->default(today())
                ->displayFormat('Y-m-d')
                ->label('To Date')
                ->closeOnDateSelection()
                ->minDate(fn($get)=>$get('from'))
                ->maxDate(today()),

        ];
    }

    public static function canView(): bool
    {
        return Str::contains(request()->path(),'cover-report');
    }
}