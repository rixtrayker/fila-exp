<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait RepRoleResources{

    // protected static function shouldRegisterNavigation(): bool
    // {
    //     return static::accessMe();
    // }
    public static function canViewAny(): bool
    {
        return static::accessMe();
    }
    public static function canView(Model $record): bool
    {
        return static::accessMe();
    }
    public static function canEdit(Model $record): bool
    {
        return static::accessMe();
    }
    public static function canCreate(): bool
    {
        return static::accessMe();
    }
    public static function canDelete(Model $record): bool
    {
        return static::accessMe();
    }
    public static function accessMe(): bool
    {
        $access = static::canAccessMe();

        if(!$access)
            return true;

        return auth()->user()->hasRole($access);
    }
    public static function canAccessMe(): array
    {
        return ['super-admin', 'medical-rep', 'area-manager', 'country-manager', 'district-manager'];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::accessMe();
    }
}
