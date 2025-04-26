<?php

namespace App\Filament\Resources\TemplateFileResource\Pages;

use App\Filament\Resources\TemplateFileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTemplateFile extends EditRecord
{
    protected static string $resource = TemplateFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
