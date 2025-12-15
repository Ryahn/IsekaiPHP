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
     */
    public static function get(string $key, $default = null)
    {
        $keys = explode('.', $key);
        $value = self::$config;

        foreach ($keys as $k) {
            if (!isset($value[$k])) {
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
            if (!isset($config[$k]) || !is_array($config[$k])) {
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
            if (!isset($value[$k])) {
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
if (!function_exists('env')) {
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

