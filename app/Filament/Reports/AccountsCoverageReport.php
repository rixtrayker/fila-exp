<?php

namespace App\Filament\Reports;

use App\Services\AccountsCoverageReportService;
use EightyNine\Reports\Components\VerticalSpace;
use EightyNine\Reports\Report;
use EightyNine\Reports\Components\Body;
use EightyNine\Reports\Components\Footer;
use EightyNine\Reports\Components\Header;
use EightyNine\Reports\Components\Text;
use Filament\Forms\Form;
use EightyNine\Reports\Components\Body\TextColumn;
use EightyNine\Reports\Components\Body\Table;
use Filament\Forms\Components\DatePicker;

class AccountsCoverageReport extends Report
{
    public ?string $heading = "Accounts Coverage Report";
    public ?string $subHeading = "A report showing medical rep account coverage statistics";
    protected static ?string $navigationLabel = "Accounts Coverage";
    protected static ?string $slug = 'accounts-coverage-report';
    protected array $panels = ['admin'];

    public function header(Header $header): Header
    {
        return $header
            ->schema([
                Header\Layout\HeaderRow::make()
                    ->schema([
                        Header\Layout\HeaderColumn::make()
                            ->schema([
                                Text::make("Accounts Coverage Report")
                                    ->title()
                                    ->primary(),
                                Text::make("Medical rep account coverage statistics with visit breakdowns")
                                    ->subtitle(),
                            ]),
                    ]),
            ]);
    }

    public function body(Body $body): Body
    {
        return $body
            ->schema([
                Table::make()
                    ->columns([
                        TextColumn::make("id")
                            ->label("ID"),
                        TextColumn::make("medical_rep_name")
                            ->label("Medical Rep")
                            ->weight('semibold'),
                        TextColumn::make("total_area_clients")
                            ->label("Clients (Total Area)")
                            ->color('info')
                            ->url(fn($record) => $record->get('client_breakdown_all_url'))
                            ->formatStateUsing(fn($state) => $state)
                            ->openUrlInNewTab(),
                        TextColumn::make("visited_doctors")
                            ->label("Visited Doctors")
                            ->color('success')
                            ->url(fn($record) => $record->get('client_breakdown_visited_url'))
                            ->formatStateUsing(fn($state) => $state)
                            ->openUrlInNewTab(),
                        TextColumn::make("unvisited_doctors")
                            ->label("Unvisited Doctors")
                            ->color('danger')
                            ->url(fn($record) => $record->get('client_breakdown_unvisited_url'))
                            ->formatStateUsing(fn($state) => $state)
                            ->openUrlInNewTab(),
                        TextColumn::make("coverage_percentage")
                            ->label("Coverage %")
                            ->suffix('%')
                            ->color(fn($state) => match (true) {
                                (float) $state >= 80 => 'success',
                                (float) $state >= 60 => 'warning',
                                default => 'danger',
                            }),
                        TextColumn::make("actual_visits")
                            ->label("Actual Visits (Done)")
                            ->color('primary')
                            ->url(fn($record) => $record->get('visit_breakdown_url'))
                            ->formatStateUsing(fn($state) => $state)
                            ->openUrlInNewTab(),
                        TextColumn::make("clinic_visits")
                            ->label("Clinic Visits")
                            ->color('gray')
                            ->url(fn($record) => $record->get('clinic_visit_breakdown_url'))
                            ->formatStateUsing(fn($state) => $state)
                            ->openUrlInNewTab(),
                        TextColumn::make("client_breakdown_all_url")->visible(false),
                        TextColumn::make("client_breakdown_visited_url")->visible(false),
                        TextColumn::make("client_breakdown_unvisited_url")->visible(false),
                        TextColumn::make("visit_breakdown_url")->visible(false),
                        TextColumn::make("clinic_visit_breakdown_url")->visible(false),
                        ])
                    ->data(
                        fn(?array $filters) => AccountsCoverageReportService::getReportData($filters)
                    ),
                VerticalSpace::make(),
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
                    ]),
            ]);
    }

    public function filterForm(Form $form): Form
    {
        return $form
            ->schema([
                DatePicker::make('from_date')
                    ->label('From Date')
                    ->default(today()->firstOfMonth()),
                DatePicker::make('to_date')
                    ->label('To Date')
                    ->default(today()),
            ]);
    }

    protected function prepareForValidation($attributes): array
    {
        return $attributes;
    }
}
