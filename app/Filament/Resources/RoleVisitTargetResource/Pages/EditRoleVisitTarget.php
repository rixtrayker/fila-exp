<?php

namespace App\Filament\Resources\RoleVisitTargetResource\Pages;

use App\Filament\Resources\RoleVisitTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRoleVisitTarget extends EditRecord
{
    protected static string $resource = RoleVisitTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
