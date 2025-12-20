<?php

namespace IsekaiPHP\Models;

use Illuminate\Database\Eloquent\Model;

// phpcs:ignore Generic.PHP.Syntax -- False positive warning
class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'description',
        'group',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
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
        $setting = self::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string $type
     * @param string|null $description
     * @param string|null $group
     * @return Setting
     */
    public static function set(
        string $key,
        $value,
        ?string $type = null,
        ?string $description = null,
        ?string $group = null
    ): Setting {
        // Determine type if not provided
        if ($type === null) {
            $type = self::detectType($value);
        }

        // Convert value to string for storage
        $storedValue = self::convertValueToString($value, $type);

        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type' => $type,
                'description' => $description,
                'group' => $group,
            ]
        );

        return $setting;
    }

    /**
     * Check if a setting exists
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return self::where('key', $key)->exists();
    }

    /**
     * Cast value based on type
     *
     * @param string $value
     * @param string $type
     * @return mixed
     */
    protected static function castValue(?string $value, string $type)
    {
        if ($value === null) {
            return null;
        }

        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'float':
                return (float) $value;
            case 'json':
                return json_decode($value, true);
            case 'string':
            default:
                return $value;
        }
    }

    /**
     * Convert value to string for storage
     *
     * @param mixed $value
     * @param string $type
     * @return string
     */
    protected static function convertValueToString($value, string $type): string
    {
        switch ($type) {
            case 'json':
                return json_encode($value);
            case 'boolean':
                return $value ? '1' : '0';
            default:
                return (string) $value;
        }
    }

    /**
     * Detect the type of a value
     *
     * @param mixed $value
     * @return string
     */
    protected static function detectType($value): string
    {
        if (is_bool($value)) {
            return 'boolean';
        }
        if (is_int($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'float';
        }
        if (is_array($value) || is_object($value)) {
            return 'json';
        }

        return 'string';
    }

    /**
     * Get all settings grouped by group
     *
     * @return array
     */
    public static function getGrouped(): array
    {
        $settings = self::orderBy('group')->orderBy('key')->get();
        $grouped = [];

        foreach ($settings as $setting) {
            $group = $setting->group ?: 'general';
            if (! isset($grouped[$group])) {
                $grouped[$group] = [];
            }
            $grouped[$group][] = $setting;
        }

        return $grouped;
    }

    /**
     * Get the actual value with proper type casting
     *
     * @param string|null $value
     * @return mixed
     */
    public function getValueAttribute(?string $value)
    {
        return self::castValue($value, $this->type);
    }

    /**
     * Accessor for value attribute (Laravel style)
     *
     * @return mixed
     */
    public function getValue(): mixed
    {
        $value = $this->attributes['value'] ?? null;

        return self::castValue($value, $this->type);
    }
}
