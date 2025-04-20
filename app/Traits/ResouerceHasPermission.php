<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\Logger;
use Illuminate\Support\Str;

trait ResouerceHasPermission
{
    /**
     * The class name of the resource
     */
    protected static string $className;

    /**
     * Initialize the trait
     */
    public static function bootResouerceHasPermission()
    {
        static::$className = static::class;
        logger()->info(static::$className);
    }

    /**
     * Get the model name from the resource class
     * The class using this trait must define a static $model property
     */
    public static function getPermissionName(): string
    {
        $permissionName = '';
        static::$className = static::class;
        $className = static::$className;
        if(property_exists($className, 'permissionName')){
            $permissionName = $className::$permissionName;
        } else {
            $permissionName = Str::kebab(class_basename(static::$className::$model));
        }
        logger()->info($permissionName);
        return $permissionName;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()->can('view '. self::getPermissionName());
    }

    public static function canView(?Model $record): bool
    {
        return auth()->user()->can('view '. self::getPermissionName());
    }

    public static function canCreate(): bool
    {
        return auth()->user()->can('create '. self::getPermissionName());
    }

    public static function canUpdate(Model $record): bool
    {
        return auth()->user()->can('edit '. self::getPermissionName());
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete '. self::getPermissionName());
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view '. self::getPermissionName());
    }
}
