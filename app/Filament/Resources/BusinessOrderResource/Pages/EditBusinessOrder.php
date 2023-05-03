<?php

namespace App\Filament\Resources\BusinessOrderResource\Pages;

use App\Filament\Resources\BusinessOrderResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBusinessOrder extends EditRecord
{
    protected static string $resource = BusinessOrderResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
