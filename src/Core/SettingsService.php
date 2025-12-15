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
            foreach ($settings as $setting) {
                $value = $setting->getValue();
                $this->cache[$setting->key] = $value;
                // Also update Config so settings override config files
                Config::set($setting->key, $value);
            }
            $this->loaded = true;
        } catch (\Exception $e) {
            // If settings table doesn't exist yet, silently fail
            // This allows the app to run before migrations are run
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
        if (!$this->loaded) {
            $this->loadSettings();
        }

        // Check cache first
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        // Try to get from database
        $value = Setting::get($key, $default);
        if ($value !== $default) {
            $this->cache[$key] = $value;
        }

        return $value;
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
    public function set(string $key, $value, ?string $type = null, ?string $description = null, ?string $group = null): void
    {
        Setting::set($key, $value, $type, $description, $group);
        
        // Update cache
        $this->cache[$key] = $value;
        
        // Update Config as well
        Config::set($key, $value);
    }

    /**
     * Check if a setting exists
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        if (!$this->loaded) {
            $this->loadSettings();
        }

        return isset($this->cache[$key]) || Setting::has($key);
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
}

