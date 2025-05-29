<?php

namespace App\Console\Commands;

use App\Jobs\CoverageReportProcess;
use App\Jobs\FrequencyReportProcess;
use App\Models\Client;
use App\Models\User;
use App\Models\Visit;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessReports extends Command
{
    protected $signature = 'reports:process
                            {--type=all : Type of report to process (all/coverage/frequency)}
                            {--from= : Start date (Y-m-d)}
                            {--to= : End date (Y-m-d)}
                            {--duration= : Time duration preset (all-time/today/yesterday/last-week/last-month/last-year)}
                            {--force : Force process even if already processed}';

    protected $description = 'Process coverage and frequency reports for a date range';

    private const VALID_REPORT_TYPES = ['all', 'coverage', 'frequency'];
    private const VALID_DURATIONS = ['all-time', 'today', 'yesterday', 'last-week', 'last-month', 'last-year'];

    public function handle()
    {
        $this->validateOptions();

        $dateRange = $this->getDateRange();
        $reportType = $this->option('type');
        $force = $this->option('force');

        $this->info("Processing {$reportType} reports from {$dateRange['from']->format('Y-m-d')} to {$dateRange['to']->format('Y-m-d')}");

        if ($reportType === 'all' || $reportType === 'coverage') {
            $this->processCoverageReports($dateRange, $force);
        }

        if ($reportType === 'all' || $reportType === 'frequency') {
            $this->processFrequencyReports($dateRange, $force);
        }

        $this->info('Reports processing jobs have been queued successfully!');
    }

    private function validateOptions(): void
    {
        $reportType = $this->option('type');
        if (!in_array($reportType, self::VALID_REPORT_TYPES)) {
            $this->error("Invalid report type. Must be one of: " . implode(', ', self::VALID_REPORT_TYPES));
            exit(1);
        }

        $duration = $this->option('duration');
        if ($duration && !in_array($duration, self::VALID_DURATIONS)) {
            $this->error("Invalid duration. Must be one of: " . implode(', ', self::VALID_DURATIONS));
            exit(1);
        }
    }

    private function getDateRange(): array
    {
        if ($this->option('from') && $this->option('to')) {
            return [
                'from' => Carbon::parse($this->option('from')),
                'to' => Carbon::parse($this->option('to'))
            ];
        }

        $duration = $this->option('duration');
        if (!$duration) {
            return [
                'from' => now()->subDays(7),
                'to' => now()
            ];
        }

        $firstVisitDate = Visit::withoutGlobalScopes()->first()?->created_at ?? Carbon::parse('2023-01-01');

        return match ($duration) {
            'today' => [
                'from' => now()->startOfDay(),
                'to' => now()->endOfDay()
            ],
            'yesterday' => [
                'from' => now()->subDay()->startOfDay(),
                'to' => now()->subDay()->endOfDay()
            ],
            'last-week' => [
                'from' => now()->subWeek()->startOfWeek(),
                'to' => now()->subWeek()->endOfWeek()
            ],
            'last-month' => [
                'from' => now()->subMonth()->startOfMonth(),
                'to' => now()->subMonth()->endOfMonth()
            ],
            'last-year' => [
                'from' => now()->subYear()->startOfYear(),
                'to' => now()->subYear()->endOfYear()
            ],
            'all-time' => [
                'from' => $firstVisitDate,
                'to' => now()
            ],
            default => [
                'from' => now()->subDays(7),
                'to' => now()
            ]
        };
    }

    private function processCoverageReports(array $dateRange, bool $force): void
    {
        $users = User::all();
        $this->info("Found {$users->count()} active users for coverage reports");

        $bar = $this->output->createProgressBar($users->count());
        $bar->start();

        foreach ($users as $user) {
            $currentDate = clone $dateRange['from'];
            while ($currentDate <= $dateRange['to']) {
                CoverageReportProcess::dispatch($user->id, $currentDate->format('Y-m-d'), $force);
                $currentDate->addDay();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }

    private function processFrequencyReports(array $dateRange, bool $force): void
    {
        $clients = Client::all();
        // $clients = Client::query()
        //     ->with(['visits' => function ($query) use ($dateFrom, $dateTo) {
        //         $query->when($dateFrom, function ($query) use ($dateFrom) {
        //             $query->where('visit_date', '>=', $dateFrom);
        //         })->when($dateTo, function ($query) use ($dateTo) {
        //             $query->where('visit_date', '<=', $dateTo);
        //         });
        //     }])
        //     ->get();

        $this->info("Found {$clients->count()} clients for frequency reports");

        $bar = $this->output->createProgressBar($clients->count());
        $bar->start();

        foreach ($clients as $client) {
            $currentDate = clone $dateRange['from'];
            while ($currentDate <= $dateRange['to']) {
                FrequencyReportProcess::dispatch($client->id, $currentDate->format('Y-m-d'), $force);
                $currentDate->addDay();
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
    }
}
