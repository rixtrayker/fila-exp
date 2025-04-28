<?php

namespace App\Filament\Resources\CoverageReportResource\Pages;

use App\Filament\Resources\CoverageReportResource;
use App\Filament\Widgets\OverallChart;
use Filament\Resources\Pages\ListRecords;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\On;

class ListCoverageReports extends ListRecords
{
    protected static string $resource = CoverageReportResource::class;

    public $from;
    public $to;
    public $user_id = [];


    protected function getHeaderWidgets(): array
    {
        return [
            // OverallChart::class,
        ];
    }

    // public function infolist(Infolist $infolist): Infolist
    // {
    //     $query = $this->getTableQuery();

    //     return $infolist
    //         ->schema([
    //             Section::make('Coverage Summary')
    //                 ->schema([
    //                     TextEntry::make('visited_count')
    //                         ->label('Done Visits')
    //                         ->getStateUsing(fn () => $query->where('status', 'visited')->count()),
    //                     TextEntry::make('missed_count')
    //                         ->label('Missed Visits')
    //                         ->getStateUsing(fn () => $query->where('status', 'missed')->count()),
    //                     TextEntry::make('pending_count')
    //                         ->label('Pending Visits')
    //                         ->getStateUsing(fn () => $query->whereIn('status', ['pending', 'planned'])->count()),
    //                 ])
    //                 ->columns(3),
    //         ]);
    // }

    // #[On('updateVisitsList')]
    // public function updateVisitsList($eventData)
    // {
    //     $this->from = $eventData['from'];
    //     $this->to = $eventData['to'];
    //     $this->user_id = $eventData['user_id'];
    //     $this->dispatch('updateVisitsList', $eventData);
    // }
}
