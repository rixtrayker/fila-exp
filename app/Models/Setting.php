<?php

namespace App\Models;

use App\Traits\ReportSyncTimestamp;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class Setting extends Model
{
    use HasFactory;
    use ReportSyncTimestamp;

    protected $fillable = [
        'order',
        'name',
        'key',
        'value',
        'type',
        'hidden',
        'description',
    ];

    private const CACHE_TTL = 60; // minutes

    public static function getSettings()
    {
        return self::withoutGlobalScopes()->get();
    }

    public static function getSetting($key)
    {
        return Cache::remember("setting.{$key}", self::CACHE_TTL, function () use ($key) {
            return self::withoutGlobalScopes()->where('key', $key)->first();
        });
    }

    public static function getClientClassDailyTargetsFromCache()
    {
        $keys = ['class-a-daily-target', 'class-b-daily-target', 'class-c-daily-target', 'class-n-daily-target', 'class-ph-daily-target'];
        $targets = [];
        foreach ($keys as $key) {
            $targets[$key] = self::getSetting($key)->value ?? 1;
        }
        return $targets;
    }

    public static function getClientClassDailyTarget($grade)
    {
        $grade = strtolower($grade);
        $targets = self::getClientClassDailyTargetsFromCache();
        return $targets['class-' . $grade . '-daily-target'];
    }

    public static function getReportSyncEnabled()
    {
        return self::getSetting('report_sync_enabled')->value ?? false;
    }

    public static function getDateFromTimestamp($timestamp = 0): Carbon
    {
        $timestamp = intval($timestamp);
        return Carbon::parse($timestamp);
    }

    // scope with hidden
    public static function scopeWithHidden($query)
    {
        return $query->withoutGlobalScopes()->where('hidden', true);
    }

    // without hidden
    public static function scopeWithoutHidden($query)
    {
        return $query->withoutGlobalScopes()->where('hidden', false);
    }

    public static function boot()
    {
        parent::boot();

        static::addGlobalScope(function ($query) {
            return $query->withoutHidden();
        });

        static::updating(function ($setting) {
            $valid = self::validateTypesAndValues($setting->type, $setting->value);
            if (!$valid) {
                throw new \Exception('Invalid type or value');
            }
        });

        static::deleted(function ($setting) {
            // Invalidate only the specific key
            Cache::forget("setting.{$setting->key}");
        });

        static::updated(function ($setting) {
            // Invalidate only the specific key
            Cache::forget("setting.{$setting->key}");
        });

        static::created(function ($setting) {
            // Invalidate only the specific key
            Cache::forget("setting.{$setting->key}");
        });
    }

    public static function getSettingsByOrder()
    {
        return self::orderBy('order')->get();
    }

    public static function validateTypesAndValues($type, $value): bool
    {
        if ($type === 'number') {
            return is_numeric($value);
        }
        if ($type === 'boolean') {
            return is_bool($value);
        }
        if ($type === 'string') {
            return is_string($value);
        }
        return false;
    }

    /**
     * Clear all individual setting caches
     * Use this in your seeder after seeding all settings
     */
    public static function clearAllSettingsCache()
    {
        // Clear all individual setting caches
        $settings = self::withoutGlobalScopes()->get();
        foreach ($settings as $setting) {
            Cache::forget("setting.{$setting->key}");
        }
    }

    /**
     * Cache all settings individually
     * Use this in your seeder after seeding all settings
     */
    public static function cacheAllSettings()
    {
        $settings = self::withoutGlobalScopes()->get();
        foreach ($settings as $setting) {
            Cache::put("setting.{$setting->key}", $setting, self::CACHE_TTL);
        }
    }
}