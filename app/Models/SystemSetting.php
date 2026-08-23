<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get(string $key, $default = null)
    {
        return Cache::rememberForever('system_setting.' . $key, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            return $setting ? $setting->value : $default;
        });
    }

    public static function set(string $key, $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('system_setting.' . $key);
        Cache::forget('system_settings.all');
    }

    public static function allCached(): array
    {
        return Cache::rememberForever('system_settings.all', function () {
            return static::pluck('value', 'key')->toArray();
        });
    }

    public static function isMaintenanceMode(): bool
    {
        return (bool) (int) static::get('maintenance_mode', 0);
    }

    public static function clearCache(): void
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget('system_setting.' . $key);
        }
        Cache::forget('system_settings.all');
    }
}
