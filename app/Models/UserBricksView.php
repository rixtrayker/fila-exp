<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserBricksView extends Model
{
    protected $table = 'user_bricks_view';

    // This is a view, so we don't want to allow updates
    public $timestamps = false;
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'user_name',
        'brick_id',
        'brick_name',
        'area_id',
        'area_name',
        'access_type'
    ];

    /**
     * Get user bricks for a specific user
     */
    public static function getUserBricks(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('user_id', $userId)->get();
    }

    /**
     * Get all brick IDs for a specific user
     */
    public static function getUserBrickIds(int $userId): array
    {
        return static::where('user_id', $userId)
            ->pluck('brick_id')
            ->toArray();
    }

    /**
     * Get all users who have access to a specific brick
     */
    public static function getBrickUsers(int $brickId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('brick_id', $brickId)->get();
    }

    /**
     * Get all users who have access to bricks in a specific area
     */
    public static function getAreaUsers(int $areaId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('area_id', $areaId)->get();
    }

    /**
     * Check if a user has access to a specific brick
     */
    public static function userHasBrickAccess(int $userId, int $brickId): bool
    {
        return static::where('user_id', $userId)
            ->where('brick_id', $brickId)
            ->exists();
    }
}
