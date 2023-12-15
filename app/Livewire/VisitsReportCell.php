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

class VisitsReportCell extends Widget implements HasForms, HasInfolists
{
    use InteractsWithInfolists;
    use InteractsWithForms;

    protected static string $view = 'livewire.visits-report-cell';
    public $record;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->record($this->record)
                ->schema([
                    TextEntry::make('medical_rep')
                        ->hiddenLabel()
                        ->columnSpanFull()
                        ->weight(FontWeight::Bold),
                    TextEntry::make('client_name')
                        ->label('Doctor'),
                    TextEntry::make('visit_date')
                        ->hiddenLabel()
                        ->state($this->record->visit_date->format('Y-m-d'))
                        ->icon('heroicon-s-clock')
                        ->iconPosition(IconPosition::Before),
                    TextEntry::make('status')
                        ->hiddenLabel()
                        ->size(TextEntry\TextEntrySize::Large)
                        ->weight(FontWeight::Bold)
                        ->badge()
                        ->color(fn (string $state): string => match ($state) {
                                'cancelled' => 'danger',
                                'planned' => 'warning',
                                'pending' => 'gray',
                                'visited' => 'success',
                                default => null,
                            })
                        ->size(TextEntry\TextEntrySize::Large)
                        ->icon(fn (string $state): string => match ($state) {
                            'cancelled' => 'heroicon-m-x-circle',
                            'planned' => 'heroicon-s-clock',
                            'pending' => 'heroicon-m-list-bullet',
                            'visited' => 'heroicon-m-check-circle',
                            default => null,
                        })
                        ->iconPosition(IconPosition::After),
                    TextEntry::make('products_list')
                        ->label('List of Products'),
                    TextEntry::make('comment')
                        ->label('Description')
                        ->columnSpanFull(),
                ])
            ->columns([
                'sm' => 2,
                'xl' => 3,
                '2xl' => 3,
            ]);
        }
}
