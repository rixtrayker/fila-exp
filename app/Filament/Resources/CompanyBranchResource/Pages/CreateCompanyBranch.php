<?php

namespace App\Filament\Resources\CompanyBranchResource\Pages;

use App\Filament\Resources\CompanyBranchResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCompanyBranch extends CreateRecord
{
    protected static bool $canCreateAnother = false;

    protected static string $resource = CompanyBranchResource::class;
}
