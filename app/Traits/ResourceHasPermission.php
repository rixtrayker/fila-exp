<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

trait ResourceHasPermission
{
    /**
     * Get the permission name for the resource
     *
     * @return string
     * @throws \RuntimeException If neither $pName nor $model is defined
     */
    public static function getPermissionName(): string
    {
        if (property_exists(static::class, 'permissionName') && static::class::$permissionName) {
            return static::class::$permissionName;
        }

        if (!property_exists(static::class, 'model')) {
            throw new \RuntimeException(
                sprintf('Neither $pName nor $model is defined in %s', static::class)
            );
        }

        return Str::kebab(class_basename(static::class::$model));
    }

    /**
     * Check if the authenticated user can view any records
     */
    public static function canViewAny(): bool
    {
        return static::checkPermission('view');
    }

    /**
     * Check if the authenticated user can view a specific record
     */
    public static function canView(?Model $record): bool
    {
        return static::checkPermission('view');
    }

    /**
     * Check if the authenticated user can create records
     */
    public static function canCreate(): bool
    {
        return static::checkPermission('create');
    }

    /**
     * Check if the authenticated user can update a specific record
     */
    public static function canUpdate(Model $record): bool
    {
        return static::checkPermission('edit');
    }

    /**
     * Check if the authenticated user can delete a specific record
     */
    public static function canDelete(Model $record): bool
    {
        return static::checkPermission('delete');
    }

    /**
     * Check if the resource should be registered in navigation
     */
    public static function shouldRegisterNavigation(): bool
    {
        return static::checkPermission('view');
    }

    /**
     * Check if the authenticated user has a specific permission
     *
     * @param string $action The permission action (view, create, edit, delete)
     * @return bool
     */
    protected static function checkPermission(string $action): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->can($action . ' ' . static::getPermissionName());
    }
}
