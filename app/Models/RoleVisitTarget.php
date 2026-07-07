<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoleVisitTarget extends Model
{
    /**
     * Settings key used as a global fallback for each client type.
     */
    public const SETTING_KEYS = [
        ClientType::AM => 'daily_am_target',
        ClientType::PM => 'daily_pm_target',
        ClientType::PH => 'daily_ph_target',
    ];

    /**
     * Hard-coded defaults when neither a role target nor a setting exists.
     * These mirror the defaults used by App\Models\Setting.
     */
    public const SETTING_DEFAULTS = [
        ClientType::AM => 2,
        ClientType::PM => 6,
        ClientType::PH => 8,
    ];

    protected $fillable = [
        'role_id',
        'client_type_id',
        'daily_target',
    ];

    protected $casts = [
        'role_id' => 'integer',
        'client_type_id' => 'integer',
        'daily_target' => 'float',
    ];

    /**
     * Per-request memo of the whole matrix: [role_id][client_type_id] => daily_target.
     */
    protected static ?array $matrixCache = null;

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function clientType()
    {
        return $this->belongsTo(ClientType::class);
    }

    /**
     * Resolve the daily visit target for a role and client type.
     *
     * Resolution rule: use the role-specific target when a matrix row exists,
     * otherwise fall back to the global settings value (daily_am_target /
     * daily_pm_target / daily_ph_target).
     */
    public static function resolveDailyTarget(?int $roleId, int $clientTypeId): float
    {
        if ($roleId !== null) {
            $matrix = static::getMatrix();

            if (isset($matrix[$roleId][$clientTypeId])) {
                return $matrix[$roleId][$clientTypeId];
            }
        }

        return static::getGlobalDailyTarget($clientTypeId);
    }

    /**
     * Get the global (settings-based) daily target for a client type.
     */
    public static function getGlobalDailyTarget(int $clientTypeId): float
    {
        $key = self::SETTING_KEYS[$clientTypeId] ?? null;

        if ($key === null) {
            return 0.0;
        }

        return (float) (Setting::getSetting($key)->value ?? self::SETTING_DEFAULTS[$clientTypeId]);
    }

    /**
     * Load the full matrix once per request; the table is tiny.
     */
    protected static function getMatrix(): array
    {
        if (static::$matrixCache === null) {
            static::$matrixCache = [];

            foreach (static::query()->get() as $target) {
                static::$matrixCache[$target->role_id][$target->client_type_id] = (float) $target->daily_target;
            }
        }

        return static::$matrixCache;
    }

    public static function flushMatrixCache(): void
    {
        static::$matrixCache = null;
    }

    protected static function booted(): void
    {
        static::saved(fn () => static::flushMatrixCache());
        static::deleted(fn () => static::flushMatrixCache());
    }
}
