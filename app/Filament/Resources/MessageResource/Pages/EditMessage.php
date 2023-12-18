<?php

namespace App\Filament\Resources\MessageResource\Pages;

use App\Filament\Resources\MessageResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMessage extends EditRecord
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make()
                    ->visible( fn($record) => now()->subMinutes(5)->isBefore($record->created_at)),
        ];
    }

    protected function authorizeAccess(): void
    {
        redirect()->to(MessageResource::getUrl('index'));
    }
}
