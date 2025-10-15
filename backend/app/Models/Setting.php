<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'description',
    ];

    /**
     * Get a setting value by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();

            if (!$setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->type);
        });
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string $group
     * @param string|null $description
     * @return static
     */
    public static function set(string $key, $value, string $type = 'string', string $group = 'general', ?string $description = null)
    {
        if (in_array($type, ['array', 'json'])) {
            $value = json_encode($value);
        }

        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'description' => $description,
            ]
        );

        Cache::forget("setting.{$key}");

        return $setting;
    }

    /**
     * Get all settings as an array
     *
     * @param string|null $group
     * @return array
     */
    public static function getAll(?string $group = null): array
    {
        $query = static::query();

        if ($group) {
            $query->where('group', $group);
        }

        return $query->get()->mapWithKeys(function ($setting) {
            return [$setting->key => static::castValue($setting->value, $setting->type)];
        })->toArray();
    }

    /**
     * Cast a value to its proper type
     *
     * @param mixed $value
     * @param string $type
     * @return mixed
     */
    protected static function castValue($value, string $type)
    {
        return match ($type) {
            'integer', 'int' => (int) $value,
            'float', 'double' => (float) $value,
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'array', 'json' => json_decode($value, true),
            default => $value,
        };
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        Cache::flush();
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::saved(function ($setting) {
            Cache::forget("setting.{$setting->key}");
        });

        static::deleted(function ($setting) {
            Cache::forget("setting.{$setting->key}");
        });
    }
}
