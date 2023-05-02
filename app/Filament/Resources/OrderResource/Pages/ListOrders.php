<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Forms\Components\FileUpload;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
            // Actions\Action::make('Import')
            // ->color('success')
            // ->action(function (array $data): void {
            //     dd($data['file']);
            // })
            // ->form([
            //     FileUpload::make('file')
            //     ->directory('sheets')
            //     ->visibility('private'),
            // ]),
        ];
    }
}
