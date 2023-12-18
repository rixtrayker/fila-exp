<?php

namespace App\Livewire;

use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Concerns\InteractsWithInfolists;
use Filament\Infolists\Contracts\HasInfolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\IconPosition;
use Livewire\Component;
use Filament\Widgets\Widget;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;

class FrequencyReportCell extends Widget implements HasForms, HasInfolists
{
    use InteractsWithInfolists;
    use InteractsWithForms;

    protected static string $view = 'livewire.frequency-report-cell';
    public $record;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
                ->schema([
                    TextEntry::make('name_en')
                    ->hiddenLabel()
                    ->columnSpanFull()
                    ->weight(FontWeight::Bold),
                    TextEntry::make('done_visits_count')
                        ->badge()
                        ->color('success')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->icon('heroicon-m-check-circle')
                        ->iconPosition(IconPosition::After),
                    TextEntry::make('pending_visits_count')
                        ->badge()
                        ->color('warning')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->icon('heroicon-s-clock')
                        ->iconPosition(IconPosition::After),
                    TextEntry::make('missed_visits_count')
                        ->badge()
                        ->color('danger')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->icon('heroicon-m-x-circle')
                        ->iconPosition(IconPosition::After),
                    TextEntry::make('total_visits_count')
                        ->badge()
                        ->color('gray')
                        ->size(TextEntry\TextEntrySize::Large)
                        ->icon('heroicon-m-list-bullet')
                        ->iconPosition(IconPosition::After),
                ])
            ->columns([
                'sm' => 2,
                'xl' => 4,
                '2xl' => 4,
            ]);
        }
}
