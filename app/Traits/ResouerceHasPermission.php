<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
trait ResouerceHasPermission{


    public static function getModelName(): string
    {
        return Str::kebab(str_replace('Resource', '', class_basename(self::class)));
    }
    public static function canViewAny(): bool
    {
        return auth()->user()->can('view '. self::getModelName());
    }

    public static function canView(?Model $record): bool
    {
        return auth()->user()->can('view '. self::getModelName());
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create '. self::getModelName());
    }

    public static function canUpdate(Model $record): bool
    {
        return auth()->user()->can('edit '. self::getModelName());
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete '. self::getModelName());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view '. self::getModelName());
    }
}
