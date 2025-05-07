<?php

namespace App\Filament\Resources\VisitResource\Pages\Traits;

use App\Services\LocationService;
use App\Services\VisitService;

trait ServiceInitializer
{
    protected LocationService $locationService;
    protected VisitService $visitService;

    public function __construct()
    {
        $this->locationService = app(LocationService::class);
        $this->visitService = app(VisitService::class);
    }
}
