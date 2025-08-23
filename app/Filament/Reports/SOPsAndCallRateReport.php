<?php

namespace App\Filament\Reports;

use App\Models\Scopes\GetMineScope;
use EightyNine\Reports\Components\VerticalSpace;
use EightyNine\Reports\Report;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use EightyNine\Reports\Components\Text;
use Filament\Forms\Form;
use EightyNine\Reports\Components\Body\TextColumn;
use EightyNine\Reports\Components\Body\Table;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use App\Models\SOPsAndCallRate;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\DatePicker;

class SOPsAndCallRateReport extends Report
{
    public ?string $heading = "SOPs and Call Rate Report";
    public ?string $subHeading = "A report for SOPs and Call Rate";
    protected static ?string $navigationLabel = "SOPs and Call Rate Report";
    protected static ?string $slug = 'sops-and-call-rate-report';
    // public ?string $subHeading = "A great report";
    // Or use the method approach
    protected array $panels = ['admin'];

    public function header(Header $header): Header
    {
        return $header
            ->schema([
                Header\Layout\HeaderRow::make()
                    ->schema([
                        Header\Layout\HeaderColumn::make()
                            ->schema([
                                Text::make("SOPs and Call Rate Report")
                                    ->title()
                                    ->primary(),
                                Text::make("A SOPs and Call Rate Report")
                                    ->subtitle(),
                            ]),
                    ]),
            ]);
    }

// 'id',
// 'name',
// 'area_name',
// 'working_days',
// 'daily_visit_target',
// 'monthly_visit_target',
// 'office_work_count',
// 'activities_count',
// 'actual_working_days',
// 'sops',
// 'actual_visits',
// 'call_rate',
// 'total_visits',
// 'vacation_days',
// 'daily_report_no'
    public function body(Body $body): Body
    {
            return $body
                ->schema([
                    Table::make()
                        ->columns([
                            TextColumn::make("id"),
                            TextColumn::make("name"),
                            // TextColumn::make("area_name"),
                            TextColumn::make("working_days"),
                            TextColumn::make("daily_visit_target"),
                            TextColumn::make("monthly_visit_target"),
                            TextColumn::make("office_work_count"),
                            TextColumn::make("activities_count"),
                            TextColumn::make("actual_working_days"),
                            TextColumn::make("sops"),
                            TextColumn::make("actual_visits"),
                            TextColumn::make("call_rate"),
                            // TextColumn::make("total_visits"),
                            TextColumn::make("vacation_days"),
                            TextColumn::make("daily_report_no"),
                        ])
                        ->data(
                            fn(?array $filters) => collect(SOPsAndCallRate::getReportDataWithFilters($filters))
                        ),
                        VerticalSpace::make(),
                        // Table::make()
                        //     ->data(
                        //         fn(?array $filters) => $this->verificationSummary($filters)
                        //     ),
                ]);
    }

    public function footer(Footer $footer): Footer
    {
        return $footer
            ->schema([
                Footer\Layout\FooterRow::make()
                    ->schema([
                        Footer\Layout\FooterColumn::make()
                            ->schema([
                                Text::make("Generated on: " . now()->format('Y-m-d H:i:s')),
                            ]),
                        Footer\Layout\FooterColumn::make()
                            ->schema([
                                Text::make("Generated on: " . now()->format('Y-m-d H:i:s')),
                            ])
                            ->alignRight(),
                    ]),
            ]);
    }

    public function filterForm(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('from_date')
                    ->label('From Date')
                    ->default(today()->startOfMonth()),
                DatePicker::make('to_date')
                    ->label('To Date')
                    ->default(today()),
                Select::make('client_type_id')
                    ->label('Client Type')
                    ->options(function () {
                        return DB::table('client_types')
                            ->pluck('name', 'id')
                            ->toArray();
                    }),
                Select::make('user_id')
                    ->label('User')
                    ->options(function () {
                        return DB::table('users')
                            ->whereIn('id', GetMineScope::getUserIds())
                            ->pluck('name', 'id')
                            ->toArray();
                    }),

            ]);
    }

    protected function prepareForValidation($attributes): array
    {
        // dd($attributes);
        return $attributes;
    }
}
