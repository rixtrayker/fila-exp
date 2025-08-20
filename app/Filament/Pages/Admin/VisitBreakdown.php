<?php

namespace App\Filament\Pages\Admin;

use App\Models\Visit;
use App\Filament\Strategies\VisitBreakdownStrategyInterface;
use App\Filament\Strategies\CoverageReportStrategy;
use App\Filament\Strategies\FrequencyReportStrategy;
use Filament\Pages\Page;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;

class VisitBreakdown extends Page implements HasTable
{
    use InteractsWithTable;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.admin.visit-breakdown';

    protected static ?string $title = 'Visit Breakdown Analysis';

    public function getTitle(): string
    {
        return $this->breakdownStrategy->getPageTitle($this->getFilters());
    }

    public ?string $fromDate = null;
    public ?string $toDate = null;
    public ?string $clientId = null;
    public ?string $status = null;
    public ?string $userId = null;
    public ?string $area = null;
    public ?string $grade = null;
    public ?string $clientTypeId = null;
    public ?string $brickId = null;
    public ?string $strategy = null;

    protected VisitBreakdownStrategyInterface $breakdownStrategy;

    public function mount(
        ?string $from_date = null,
        ?string $to_date = null,
        ?string $client_id = null,
        ?string $status = null,
        ?string $user_id = null,
        ?string $area = null,
        ?string $grade = null,
        ?string $client_type_id = null,
        ?string $brick_id = null,
        ?string $strategy = null
    ): void {
        $this->fromDate = $from_date ?? now()->startOfMonth()->format('Y-m-d');
        $this->toDate = $to_date ?? now()->endOfDay()->format('Y-m-d');
        $this->clientId = $client_id;
        $this->status = $status;
        $this->userId = $user_id;
        $this->area = $area ? (is_string($area) ? explode(',', $area) : $area) : null;
        $this->grade = $grade ? (is_string($grade) ? explode(',', $grade) : $grade) : null;
        $this->clientTypeId = $client_type_id ? (is_string($client_type_id) ? explode(',', $client_type_id) : $client_type_id) : null;
        $this->brickId = $brick_id ? (is_string($brick_id) ? explode(',', $brick_id) : $brick_id) : null;
        $this->strategy = $strategy ?? 'coverage';

        // Initialize strategy based on the strategy parameter
        $this->breakdownStrategy = $this->strategy === 'frequency' 
            ? new FrequencyReportStrategy() 
            : new CoverageReportStrategy();
    }

    public function table(Table $table): Table
    {
        $query = $this->breakdownStrategy->configureQuery(Visit::query(), $this->getFilters());
        
        return $table
            ->query($query)
            ->columns($this->breakdownStrategy->getTableColumns())
            ->filters($this->breakdownStrategy->getTableFilters())
            ->actions($this->breakdownStrategy->getTableActions())
            ->bulkActions([
                // Add bulk actions if needed
            ])
            ->emptyStateHeading('No visits found')
            ->emptyStateDescription('Try adjusting your filters to see more results.')
            ->emptyStateIcon('heroicon-o-calendar-days')
            ->defaultSort('visit_date', 'desc')
            ->poll('30s') // Auto-refresh every 30 seconds
            ->striped();
    }

    public function getStats(): array
    {
        return $this->breakdownStrategy->getStats($this->getFilters());
    }

    public function getClientBreakdown(): array
    {
        return $this->breakdownStrategy->getClientBreakdown($this->getFilters());
    }


    protected function getFilters(): array
    {
        return [
            'from_date' => $this->fromDate,
            'to_date' => $this->toDate,
            'client_id' => $this->clientId,
            'status' => $this->status,
            'user_id' => $this->userId,
            'area' => $this->area,
            'grade' => $this->grade,
            'client_type_id' => $this->clientTypeId,
            'brick_id' => $this->brickId,
        ];
    }
}