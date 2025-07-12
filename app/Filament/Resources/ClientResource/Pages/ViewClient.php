<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('viewLocation')
                ->label('View Location')
                ->icon('heroicon-o-map-pin')
                ->color(fn () => $this->record->mapUrl ? 'info' : 'gray')
                ->disabled(fn () => !$this->record->mapUrl)
                ->url(fn () => $this->record->mapUrl)
                ->openUrlInNewTab()
                ->tooltip(fn () => $this->record->mapUrl
                    ? 'Open location in Google Maps'
                    : 'No location set for this client'),
        ];
    }
}
