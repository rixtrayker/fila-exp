<?php

namespace App\Filament\Resources\RoleVisitTargetResource\Pages;

use App\Filament\Resources\RoleVisitTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRoleVisitTargets extends ListRecords
{
    protected static string $resource = RoleVisitTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
