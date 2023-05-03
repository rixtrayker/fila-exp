<?php

namespace App\Filament\Resources\BusinessOrderResource\Pages;

use App\Filament\Resources\BusinessOrderResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBusinessOrder extends CreateRecord
{
    protected static string $resource = BusinessOrderResource::class;
}
