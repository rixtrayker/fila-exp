<?php

namespace App\Traits;

use App\Models\EditRequest;
use App\Models\Scopes\GetMineScope;
use Carbon\Carbon;

trait HasRoleScopeTrait {
    // public static function boot()
    // {
    //     parent::boot();
    //     if(!auth()->user()->hasRole('medical-rep')){
    //         static::addGlobalScope(new GetMineScope);

    //     }
    // }

    public static function bootHasRoleScopeTrait()
    {
        // if(!auth()->user()->hasRole('medical-rep')) {
            static::addGlobalScope(new GetMineScope);
        // }
    }
}
