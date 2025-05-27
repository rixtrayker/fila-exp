<?php

namespace App\Traits;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

trait ReportSyncTimestamp
{
    private static function updateTimestamp($key, $value, ?callable $validator = null)
    {
        if ($validator) {
            $value = $validator($value);
        }

        DB::table('settings')->where('key', $key)->update(['value' => $value]);

        // Invalidate only the specific key
        Cache::forget("setting.{$key}");
        // Also invalidate the all settings cache since it contains this key
        Cache::forget('all_settings');
    }

    private static function validateTimestamp($value, $oldTimestamp)
    {
        $value = intval($value);
        if ($oldTimestamp && $oldTimestamp->timestamp >= $value) {
            return null;
        }
        return $value;
    }

    private static function getTimestamp(string $key): Carbon
    {
        $value = self::getSetting($key)?->value ?? null;
        return self::getDateFromTimestamp($value);
    }

    private static function updateTimestampWithValidation(string $key, $timestamp)
    {
        $old = self::getTimestamp($key);
        $validatedTimestamp = self::validateTimestamp($timestamp, $old);
        if ($validatedTimestamp === null) {
            return;
        }
        self::updateTimestamp($key, $validatedTimestamp);
    }

    public static function getFrequencyReportSyncTimestamp(): Carbon
    {
        return self::getTimestamp('frequency_report_sync_timestamp');
    }

    public static function getCoverageReportSyncTimestamp(): Carbon
    {
        return self::getTimestamp('coverage_report_sync_timestamp');
    }

    public static function updateFrequencyReportSyncTimestamp($timestamp)
    {
        self::updateTimestampWithValidation('frequency_report_sync_timestamp', $timestamp);
    }

    public static function updateCoverageReportSyncTimestamp($timestamp)
    {
        self::updateTimestampWithValidation('coverage_report_sync_timestamp', $timestamp);
    }

    public static function updateLastSyncTimestamp($timestamp)
    {
        // Example of custom validation that just ensures it's a positive integer
        $validator = function($value) {
            $value = intval($value);
            return $value > 0 ? $value : null;
        };

        self::updateTimestamp('last_sync_timestamp', $timestamp, $validator);
    }
}
