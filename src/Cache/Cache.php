<?php

namespace IsekaiPHP\Cache;

use IsekaiPHP\Core\Config;

/**
 * Cache Facade
 * 
 * Static facade for easy cache access.
 */
class Cache
{
    protected static ?CacheManager $manager = null;

    /**
     * Get cache manager instance
     */
    protected static function manager(): CacheManager
    {
        if (self::$manager === null) {
            $config = Config::get('cache', []);
            self::$manager = new CacheManager($config);
        }

        return self::$manager;
    }

    /**
     * Get an item from cache
     */
    public static function get(string $key, $default = null)
    {
        return self::manager()->get($key, $default);
    }

    /**
     * Store an item in cache
     */
    public static function put(string $key, $value, ?int $ttl = null): bool
    {
        return self::manager()->put($key, $value, $ttl);
    }

    /**
     * Store an item in cache if it doesn't exist
     */
    public static function add(string $key, $value, ?int $ttl = null): bool
    {
        return self::manager()->add($key, $value, $ttl);
    }

    /**
     * Get an item from cache or store the default value
     */
    public static function remember(string $key, callable $callback, ?int $ttl = null)
    {
        return self::manager()->remember($key, $callback, $ttl);
    }

    /**
     * Remove an item from cache
     */
    public static function forget(string $key): bool
    {
        return self::manager()->forget($key);
    }

    /**
     * Clear all cache
     */
    public static function flush(): bool
    {
        return self::manager()->flush();
    }

    /**
     * Check if an item exists in cache
     */
    public static function has(string $key): bool
    {
        return self::manager()->has($key);
    }
}

