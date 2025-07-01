<?php

namespace App\Filament\Strategies;

use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

interface VisitBreakdownStrategyInterface
{
    /**
     * Configure the table query based on the strategy
     */
    public function configureQuery(Builder $query, array $filters): Builder;
    
    /**
     * Get the appropriate table columns for this strategy
     */
    public function getTableColumns(): array;
    
    /**
     * Get filters specific to this strategy
     */
    public function getTableFilters(): array;
    
    /**
     * Get table actions specific to this strategy
     */
    public function getTableActions(): array;
    
    /**
     * Get statistics data for this strategy
     */
    public function getStats(array $filters): array;
    
    /**
     * Get the page title for this strategy
     */
    public function getPageTitle(array $filters): string;
    
    /**
     * Get the table heading for this strategy
     */
    public function getTableHeading(): string;
    
    /**
     * Get client breakdown data (if applicable for this strategy)
     */
    public function getClientBreakdown(array $filters): array;
}