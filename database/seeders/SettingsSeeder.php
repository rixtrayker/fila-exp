<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'order' => 1,
                'name' => 'Medical Rep KM Price',
                'description' => 'The price of a KM for a medical rep',
                'key' => 'medical-rep-km-price',
                'is_presistent' => true,
                'type' => 'number',
                'value' => 1.5,
            ],
            [
                'order' => 2,
                'name' => 'District Manager KM Price',
                'description' => 'The price of a KM for a district manager',
                'key' => 'district-manager-km-price',
                'is_presistent' => true,
                'type' => 'number',
                'value' => 2.5,
            ],
            [
                'order' => 3,
                'name' => 'Medical Rep Daily Allowance',
                'description' => 'The daily allowance for a medical rep',
                'key' => 'medical-rep-daily-allowance',
                'is_presistent' => true,
                'type' => 'number',
                'value' => 90,
            ],
            [
                'order' => 4,
                'name' => 'District Manager Daily Allowance',
                'description' => 'The daily allowance for a district manager',
                'key' => 'district-manager-daily-allowance',
                'is_presistent' => true,
                'type' => 'number',
                'value' => 150,
            ],
            [
                'order' => 5,
                'name' => 'Visit Distance',
                'description' => 'The distance a medical rep can visit in a day',
                'type' => 'number',
                'key' => 'visit-distance',
                'value' => 300,
            ],
            [
                'order' => 6,
                'name' => 'Visits Target',
                'description' => 'The target number of visits a medical rep can make in a day',
                'type' => 'number',
                'is_presistent' => true,
                'key' => 'visits-target',
                'value' => 8,
            ],
            [
                'order' => 7,
                'name' => 'Class A Daily Target',
                'description' => 'The target number of visits a medical reps should make in a day for Class A clients',
                'type' => 'number',
                'is_presistent' => true,
                'key' => 'class-a-daily-target',
                'value' => 10,
            ],
            [
                'order' => 8,
                'name' => 'Class B Daily Target',
                'description' => 'The target number of visits a medical reps should make in a day for Class B clients',
                'type' => 'number',
                'is_presistent' => true,
                'key' => 'class-b-daily-target',
                'value' => 8,
            ],
            [
                'order' => 9,
                'name' => 'Class C Daily Target',
                'description' => 'The target number of visits a medical reps should make in a day for Class C clients',
                'type' => 'number',
                'is_presistent' => true,
                'key' => 'class-c-daily-target',
                'value' => 6,
            ],
            [
                'order' => 10,
                'name' => 'Class N Daily Target',
                'description' => 'The target number of visits a medical reps should make in a day for Class N clients',
                'type' => 'number',
                'is_presistent' => true,
                'key' => 'class-n-daily-target',
                'value' => 4,
            ],
            [
                'order' => 11,
                'name' => 'Class PH Daily Target',
                'description' => 'The target number of visits a medical reps should make in a day for Class PH clients',
                'type' => 'number',
                'is_presistent' => true,
                'key' => 'class-ph-daily-target',
                'value' => 2,
            ],
            [
                'order' => 12,
                'name' => 'Report Sync Enabled',
                'key' => 'report_sync_enabled',
                'description' => 'Enable/disable automatic report synchronization',
                'type' => 'boolean',
                'value' => true,
                'is_presistent' => true,
            ],
            [
                'order' => 13,
                'name' => 'Frequency Report Sync Cursor Index',
                'key' => 'frequency_report_sync_cursor_index',
                'description' => 'Last processed cursor index for frequency report sync (hidden)',
                'type' => 'number',
                'is_presistent' => true,
                'hidden' => true,
            ],
            [
                'order' => 14,
                'name' => 'Coverage Report Sync Cursor Index',
                'key' => 'coverage_report_sync_cursor_index',
                'description' => 'Last processed cursor index for coverage report sync (hidden)',
                'type' => 'number',
                'is_presistent' => true,
                'hidden' => true,
            ],
        ];

        foreach ($settings as $setting) {
            if (isset($setting['is_presistent']) && $setting['is_presistent']) {
                // just update these order, name,type, hidden and description
                unset($setting['value']);
                unset($setting['is_presistent']);
                unset($setting['hidden']);

                Setting::updateOrCreate(
                    ['key' => $setting['key']],
                    $setting
                );

            } else {
                Setting::updateOrCreate(
                    ['key' => $setting['key']],
                    $setting
                );
            }
        }

        Setting::whereNotIn('key', array_column($settings, 'key'))->delete();
        // clear cached settings key is settings
        Setting::clearAllSettingsCache();
        Setting::cacheAllSettings();
    }
}
