<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\Admin\FrequencyReport;
use App\Models\Client;
use App\Models\ClientType;
use App\Models\User;
use App\Models\VisitType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Widgets\Widget;
use Illuminate\Support\Str;

class VisitsFilterFormWidget extends Widget implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.widgets.visits-filter-form-widget';
    public $from;
    public $to;
    public $user_id = [];
    public $grade = [];
    public $client_type_id = [];
    public $visit_type_id = [];
    public $query = [];

    private static $medicalReps;
    private static $avgGrade;

    protected $listeners = ['refreshData'];

    // public function queryString(){
    //     return [
    //         'from',
    //         'to',
    //         'user_id',
    //         'grade',
    //     ];
    // }

    public function refreshData(){
        $this->dispatch('updateReportData', [
            'from' => $this->from,
            'to' => $this->to,
            'user_id' => $this->user_id,
            'grade' => $this->grade,
            'client_type_id' => $this->client_type_id,
            'visit_type_id' => $this->visit_type_id,
        ]);
    }

    public function getColumnSpan(): int | string | array
    {
        return 'full';
    }

    protected function getFormSchema()
    {
        return [

            Select::make('user_id')
                ->label('Medical Rep')
                ->default($this->user_id)
                ->multiple()
                ->options(self::getMedicalReps()),
            // ->getOptionLabelUsing(fn ($value): ?string => User::find($value)?->name)
            Select::make('grade')
                ->default($this->grade)
                ->options(fn()=>self::gradeAVG()),

            Select::make('visit_type_id')
                ->label('Visit Type')
                ->multiple()
                ->options(VisitType::pluck('name','id')),
            Select::make('client_type_id')
                ->label('Client Type')
                ->multiple()
                ->options(ClientType::pluck('name','id')),
            // ->query(function (Builder $query, array $data): Builder {
            //     if(count($data['values'])){
            //         return $query->rightJoin('visits', 'clients.id', '=', 'visits.client_id')
            //             ->whereIn('visits.user_id', $data['values']);
            //     }
            //     else
            //         return $query;
            //     }),
            DatePicker::make('from')
                ->default($this->from ?? today()->subDays(7)),
            DatePicker::make('to')
                ->default($this->to ?? today()),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema($this->getFormSchema())->columns([
                'lg' => 4,
                'md' => 2,
                'sm' => 1,
            ]);
    }
    private static function gradeAVG(): array
    {
        if(self::$avgGrade)
            return self::$avgGrade;
        $query = Client::query()
            ->select('grade')
            ->selectRaw('SUM(CASE WHEN visits.status = "visited" THEN 1 ELSE 0 END) AS done_visits')
            ->selectRaw('SUM(CASE WHEN visits.status = "cancelled" THEN 1 ELSE 0 END) AS missed_visits')
            ->leftJoin('visits', 'clients.id', '=', 'visits.client_id')
            ->groupBy('grade')
            ->get();

        $output = [
            'A' => 'A - 0 %',
            'B' => 'B - 0 %',
            'C' => 'C - 0 %',
            'N' => 'N - 0 %',
            'PH' => 'PH - 0 %',
        ];

        foreach ($query as $result) {
            $grade = $result->grade;
            $done_visits = $result->done_visits;
            $missed_visits = $result->missed_visits;

            $total = $done_visits + $missed_visits;

            if ($total) {
                $percentage = round($done_visits / $total, 4) * 100;
            } else {
                $percentage = 0;
            }

            $output[$grade] = $grade . ' - ' . $percentage . ' %';
        }
        self::$avgGrade = $output;
        return $output;
    }

    private static function getMedicalReps(): array
    {
        if(self::$medicalReps)
            return self::$medicalReps;

        self::$medicalReps = User::getMine()->pluck('name','id')->toArray();
        return self::$medicalReps;
    }
}
