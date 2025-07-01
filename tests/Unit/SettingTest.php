<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Setting::query()->delete();
    }

    public function test_get_setting_caches_result()
    {
        $setting = Setting::create([
            'order' => 1,
            'name' => 'Test Setting',
            'key' => 'test-key',
            'value' => 'test-value',
            'type' => 'string',
        ]);
        $result1 = Setting::getSetting('test-key');
        $this->assertEquals('test-value', $result1->value);
        $result2 = Setting::getSetting('test-key');
        $this->assertEquals('test-value', $result2->value);
        $this->assertTrue(Cache::has('setting.test-key'));
    }

    public function test_get_settings_caches_all_settings()
    {
        Setting::create([
            'order' => 1,
            'name' => 'Test Setting 1',
            'key' => 'test-key-1',
            'value' => 'test-value-1',
            'type' => 'string',
        ]);
        Setting::create([
            'order' => 2,
            'name' => 'Test Setting 2',
            'key' => 'test-key-2',
            'value' => 'test-value-2',
            'type' => 'string',
        ]);
        $settings1 = Setting::getSettings();
        $filtered1 = $settings1->whereIn('key', ['test-key-1', 'test-key-2']);
        $this->assertCount(2, $filtered1);
        $settings2 = Setting::getSettings();
        $filtered2 = $settings2->whereIn('key', ['test-key-1', 'test-key-2']);
        $this->assertCount(2, $filtered2);
        $this->assertTrue(Cache::has('all_settings'));
    }

    public function test_cache_invalidation_on_update()
    {
        $setting = Setting::create([
            'order' => 1,
            'name' => 'Test Setting',
            'key' => 'test-key',
            'value' => 'test-value',
            'type' => 'string',
        ]);
        Setting::getSetting('test-key');
        $setting->update(['value' => 'new-value']);
        $updatedSetting = Setting::getSetting('test-key');
        $this->assertEquals('new-value', $updatedSetting->value);
        $this->assertTrue(Cache::has('setting.test-key'));
        $this->assertEquals('new-value', Cache::get('setting.test-key')->value);
    }

    public function test_cache_invalidation_on_delete()
    {
        $setting = Setting::create([
            'order' => 1,
            'name' => 'Test Setting',
            'key' => 'test-key',
            'value' => 'test-value',
            'type' => 'string',
        ]);
        Setting::getSetting('test-key');
        $setting->delete();
        $deletedSetting = Setting::getSetting('test-key');
        $this->assertNull($deletedSetting);
        $this->assertFalse(Cache::has('setting.test-key'));
    }

    public function test_clear_all_settings_cache()
    {
        Setting::create([
            'order' => 1,
            'name' => 'Test Setting 1',
            'key' => 'test-key-1',
            'value' => 'test-value-1',
            'type' => 'string',
        ]);
        Setting::create([
            'order' => 2,
            'name' => 'Test Setting 2',
            'key' => 'test-key-2',
            'value' => 'test-value-2',
            'type' => 'string',
        ]);
        Setting::getSettings();
        Setting::getSetting('test-key-1');
        Setting::getSetting('test-key-2');
        Setting::clearAllSettingsCache();
        $this->assertFalse(Cache::has('all_settings'));
        $this->assertFalse(Cache::has('setting.test-key-1'));
        $this->assertFalse(Cache::has('setting.test-key-2'));
    }

    public function test_cache_all_settings()
    {
        Setting::create([
            'order' => 1,
            'name' => 'Test Setting 1',
            'key' => 'test-key-1',
            'value' => 'test-value-1',
            'type' => 'string',
        ]);
        Setting::create([
            'order' => 2,
            'name' => 'Test Setting 2',
            'key' => 'test-key-2',
            'value' => 'test-value-2',
            'type' => 'string',
        ]);
        Cache::flush();
        Setting::cacheAllSettings();
        $this->assertTrue(Cache::has('all_settings'));
        $this->assertTrue(Cache::has('setting.test-key-1'));
        $this->assertTrue(Cache::has('setting.test-key-2'));
        $cachedSetting1 = Cache::get('setting.test-key-1');
        $this->assertEquals('test-value-1', $cachedSetting1->value);
        $cachedSetting2 = Cache::get('setting.test-key-2');
        $this->assertEquals('test-value-2', $cachedSetting2->value);
    }
}
