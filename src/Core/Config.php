<?php

namespace IsekaiPHP\Core;

use Dotenv\Dotenv;

class Config
{
    protected static array $config = [];
    protected static bool $loaded = false;

    /**
     * Load environment variables and configuration files
     */
    public static function load(string $basePath): void
    {
        if (self::$loaded) {
            return;
        }

        // Load .env file
        if (file_exists($basePath . '/.env')) {
            $dotenv = Dotenv::createImmutable($basePath);
            $dotenv->load();
        }

        // Load config files
        $configPath = $basePath . '/config';
        if (is_dir($configPath)) {
            $files = glob($configPath . '/*.php');
            foreach ($files as $file) {
                $key = basename($file, '.php');
                self::$config[$key] = require $file;
            }
        }

        self::$loaded = true;
    }

    /**
     * Get a configuration value
     *
     * Supports both dot notation (nested) and underscore notation (flat).
     * - For dot notation (e.g., 'cache.default'): checks nested structure first, then flat underscore key
     * - For underscore notation (e.g., 'cache_default'): checks flat key first, then nested structure
     */
    public static function get(string $key, $default = null)
    {
        // Check if key contains dots (dot notation) or underscores (flat notation)
        $hasDots = strpos($key, '.') !== false;
        $hasUnderscores = strpos($key, '_') !== false;

        // If key uses dot notation, check flat underscore version first (database settings take priority)
        if ($hasDots && ! $hasUnderscores) {
            // First check flat underscore version (database settings override config files)
            $underscoreKey = str_replace('.', '_', $key);
            if (isset(self::$config[$underscoreKey])) {
                return self::$config[$underscoreKey];
            }

            // Not found as flat key, try nested structure (config file values)
            $keys = explode('.', $key);
            $value = self::$config;

            foreach ($keys as $k) {
                if (! isset($value[$k])) {
                    return $default;
                }
                $value = $value[$k];
            }

            return $value;
        }

        // If key uses underscore notation, try flat key first
        if ($hasUnderscores && ! $hasDots) {
            // Check flat key directly
            if (isset(self::$config[$key])) {
                return self::$config[$key];
            }

            // Not found as flat key, try nested structure
            $dotKey = str_replace('_', '.', $key);
            $keys = explode('.', $dotKey);
            $value = self::$config;

            foreach ($keys as $k) {
                if (! isset($value[$k])) {
                    return $default;
                }
                $value = $value[$k];
            }

            return $value;
        }

        // Key has both dots and underscores, or neither - use original logic
        // This handles simple keys like 'app' or edge cases
        if (isset(self::$config[$key])) {
            return self::$config[$key];
        }

        // Try as nested structure
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (! isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    /**
     * Set a configuration value
     */
    public static function set(string $key, $value): void
    {
        $keys = explode('.', $key);
        $config = &self::$config;

        foreach ($keys as $k) {
            if (! isset($config[$k]) || ! is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }

        $config = $value;
    }

    /**
     * Check if a configuration key exists
     */
    public static function has(string $key): bool
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (! isset($value[$k])) {
                return false;
            }
            $value = $value[$k];
        }

        return true;
    }
}

/**
 * Helper function to get environment variable
 */
if (! function_exists('env')) {
    function env(string $key, $default = null)
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}
