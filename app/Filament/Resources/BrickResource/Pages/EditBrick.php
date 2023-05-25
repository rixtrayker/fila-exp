<?php

namespace App\Filament\Resources\BrickResource\Pages;

use App\Filament\Resources\BrickResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBrick extends EditRecord
{
    protected static string $resource = BrickResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
