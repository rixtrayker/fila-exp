<?php

namespace App\Filament\Resources\TemplateFileResource\Pages;

use App\Filament\Resources\TemplateFileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTemplateFiles extends ListRecords
{
    protected static string $resource = TemplateFileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Upload Template')
                ->icon('heroicon-o-plus'),
        ];
    }
}
