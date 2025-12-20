<?php

namespace IsekaiPHP\Core;

use IsekaiPHP\Models\Setting;

/**
 * Service for managing application settings stored in database
 */
class SettingsService
{
    protected array $cache = [];
    protected bool $loaded = false;

    /**
     * Load all settings from database into cache
     */
    public function loadSettings(): void
    {
        if ($this->loaded) {
            return;
        }

        try {
            $settings = Setting::all();

            // Debug: Log if we have settings
            if ($settings->count() > 0) {
                error_log('SettingsService: Loading ' . $settings->count() . ' settings from database');
            }

            foreach ($settings as $setting) {
                try {
                    // Get the key first
                    $key = $setting->key ?? null;
                    if (! $key) {
                        continue;
                    }

                    // Get the value
                    $value = $setting->getValue();

                    // Cache with the actual key
                    $this->cache[$key] = $value;

                    // Also cache with alternative format for compatibility
                    $altKey = $this->convertKeyFormat($key);
                    if ($altKey !== $key) {
                        $this->cache[$altKey] = $value;
                    }

                    // Also update Config so settings override config files
                    // Store keys as-is with underscores (no conversion to dots)
                    Config::set($key, $value);
                } catch (\Exception $e) {
                    error_log(
                        'SettingsService: Error processing setting ' . ($setting->key ?? 'unknown') . ': ' .
                        $e->getMessage()
                    );
                }
            }
            $this->loaded = true;
        } catch (\Exception $e) {
            // Log the error instead of silently failing
            error_log('SettingsService: Error loading settings: ' . $e->getMessage());
            error_log('SettingsService: Stack trace: ' . $e->getTraceAsString());
            // Still mark as loaded to prevent infinite retries
            $this->loaded = true;
        }
    }

    /**
     * Get a setting value
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        if (! $this->loaded) {
            $this->loadSettings();
        }

        // Check cache first (exact match)
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        // Try to get from database (exact match)
        $value = Setting::get($key, $default);
        // If we got a value (even if it's empty string, it's still a value from DB)
        // Only use default if the value is exactly the default (meaning not found)
        if ($value !== $default || ($value === null && $default !== null)) {
            $this->cache[$key] = $value;

            return $value !== null ? $value : $default;
        }

        // If not found, try alternative format (dot <-> underscore conversion)
        $altKey = $this->convertKeyFormat($key);
        if ($altKey !== $key) {
            // Check cache with alternative key
            if (isset($this->cache[$altKey])) {
                // Cache the value with the requested key for future lookups
                $this->cache[$key] = $this->cache[$altKey];

                return $this->cache[$altKey];
            }

            // Try database with alternative key
            $value = Setting::get($altKey, $default);
            if ($value !== $default) {
                // Cache with both keys for future lookups
                $this->cache[$key] = $value;
                $this->cache[$altKey] = $value;

                return $value;
            }
        }

        return $default;
    }

    /**
     * Convert key format between dot and underscore notation
     *
     * @param string $key
     * @return string
     */
    protected function convertKeyFormat(string $key): string
    {
        // If key contains dots, convert to underscores
        if (strpos($key, '.') !== false) {
            return str_replace('.', '_', $key);
        }
        // If key contains underscores, convert to dots
        if (strpos($key, '_') !== false) {
            return str_replace('_', '.', $key);
        }

        // No conversion needed
        return $key;
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $type
     * @param string|null $description
     * @param string|null $group
     * @return void
     */
    public function set(
        string $key,
        $value,
        ?string $type = null,
        ?string $description = null,
        ?string $group = null
    ): void {
        Setting::set($key, $value, $type, $description, $group);

        // Get the properly cast value
        $castValue = Setting::get($key, $value);

        // Update cache
        $this->cache[$key] = $castValue;

        // Update Config as well so it's available throughout the application
        // Store key as-is: underscore keys will be stored flat, dot keys will be nested
        Config::set($key, $castValue);
    }

    /**
     * Check if a setting exists
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        if (! $this->loaded) {
            $this->loadSettings();
        }

        // Check cache first (exact match)
        if (isset($this->cache[$key])) {
            return true;
        }

        // Check database (exact match)
        if (Setting::has($key)) {
            return true;
        }

        // Try alternative format
        $altKey = $this->convertKeyFormat($key);
        if ($altKey !== $key) {
            if (isset($this->cache[$altKey]) || Setting::has($altKey)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clear the settings cache
     */
    public function clearCache(): void
    {
        $this->cache = [];
        $this->loaded = false;
    }

    /**
     * Get all settings grouped by group
     *
     * @return array
     */
    public function getGrouped(): array
    {
        return Setting::getGrouped();
    }

    /**
     * Get cache for debugging
     *
     * @return array
     */
    public function getCache(): array
    {
        return $this->cache;
    }

    /**
     * Check if settings are loaded
     *
     * @return bool
     */
    public function isLoaded(): bool
    {
        return $this->loaded;
    }
}
