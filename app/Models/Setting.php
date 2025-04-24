<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'order',
        'name',
        'key',
        'value',
        'type',
        'description',
    ];
    public static function getSettings()
    {
        if (Cache::has('settings')) {
            return Cache::get('settings');
        }

        return self::cacheSettings();
    }

    public static function cacheSettings()
    {
        return Cache::remember('settings', 60, function () {
            return self::all();
        });
    }

    public static function getSetting($key)
    {
        $settings = self::getSettings();
        return $settings->where('key', $key)->first();
    }

    public static function boot()
    {
        parent::boot();
        static::updating(function ($setting) {
            $valid = self::validateTypesAndValues($setting->type, $setting->value);
            if (!$valid) {
                throw new \Exception('Invalid type or value');
            }
        });

        static::deleted(function ($setting) {
            Cache::forget('settings');
        });

        static::updated(function ($setting) {
            Cache::forget('settings');
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
}