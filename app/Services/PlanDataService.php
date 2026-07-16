<?php

namespace App\Services;

final class PlanDataService
{
    public const DAYS = [
        'sat',
        'sun',
        'mon',
        'tue',
        'wed',
        'thu',
        'fri',
    ];

    private const SHIFT_FIELDS = [
        'am_shift',
        'pm_shift',
        'am_time',
        'pm_time',
    ];

    private const LEGACY_DAY_ALIASES = [
        'tues' => 'tue',
        'wednes' => 'wed',
        'thurs' => 'thu',
    ];

    /**
     * Move weekly-plan form fields into the plan_data JSON payload.
     */
    public static function extract(array &$data): array
    {
        $planData = [];

        foreach (self::DAYS as $day) {
            $clientsKey = "{$day}_clients";

            if (array_key_exists($clientsKey, $data)) {
                $clients = array_values(array_unique(array_map(
                    'intval',
                    array_filter((array) $data[$clientsKey]),
                )));

                if ($clients !== []) {
                    $planData[$clientsKey] = $clients;
                }

                unset($data[$clientsKey]);
            }

            foreach (self::SHIFT_FIELDS as $field) {
                $key = "{$day}_{$field}";

                if (! array_key_exists($key, $data)) {
                    continue;
                }

                $value = $data[$key];

                if (str_ends_with($field, '_shift') && $value !== null && $value !== '') {
                    $value = (int) $value;
                }

                if ($value !== null && $value !== '') {
                    $planData[$key] = $value;
                }

                unset($data[$key]);
            }
        }

        return $planData;
    }

    /**
     * Convert existing long weekday prefixes to the canonical keys.
     */
    public static function normalize(array $planData): array
    {
        foreach (self::LEGACY_DAY_ALIASES as $legacyDay => $canonicalDay) {
            foreach (array_keys($planData) as $key) {
                $legacyPrefix = "{$legacyDay}_";

                if (! str_starts_with($key, $legacyPrefix)) {
                    continue;
                }

                $canonicalKey = $canonicalDay.substr($key, strlen($legacyDay));
                $planData[$canonicalKey] = $planData[$key];
                unset($planData[$key]);
            }
        }

        return $planData;
    }
}
