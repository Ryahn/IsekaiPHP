<?php

namespace IsekaiPHP\Cache;

use IsekaiPHP\Core\Config;

/**
 * Cache Manager
 * 
 * Manages cache operations with multiple drivers.
 */
class CacheManager
{
    protected array $drivers = [];
    protected string $defaultDriver;
    protected array $config;
    protected array $customDrivers = [];

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->defaultDriver = $config['default'] ?? 'file';
    }

    /**
     * Register a custom cache driver
     */
    public function extend(string $driver, callable $callback): void
    {
        $this->customDrivers[$driver] = $callback;
    }

    /**
     * Get a cache driver instance
     */
    public function driver(?string $driver = null): CacheInterface
    {
        $driver = $driver ?? $this->defaultDriver;

        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    /**
     * Create a cache driver instance
     */
    protected function createDriver(string $driver): CacheInterface
    {
        $config = $this->config['stores'][$driver] ?? [];

        // Check for custom driver first (registered by modules)
        if (isset($this->customDrivers[$driver])) {
            $instance = call_user_func($this->customDrivers[$driver], $config);
            if ($instance instanceof CacheInterface) {
                return $instance;
            }
        }

        // Use built-in drivers
        return match ($driver) {
            'file' => new Drivers\FileCache($config),
            'array' => new Drivers\ArrayCache($config),
            'redis' => new Drivers\RedisCache($config),
            default => throw new \Exception("Cache driver [{$driver}] is not supported."),
        };
    }

    /**
     * Get an item from cache
     */
    public function get(string $key, $default = null)
    {
        return $this->driver()->get($key, $default);
    }

    /**
     * Store an item in cache
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        return $this->driver()->put($key, $value, $ttl);
    }

    /**
     * Store an item in cache if it doesn't exist
     */
    public function add(string $key, $value, ?int $ttl = null): bool
    {
        return $this->driver()->add($key, $value, $ttl);
    }

    /**
     * Get an item from cache or store the default value
     */
    public function remember(string $key, callable $callback, ?int $ttl = null)
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->put($key, $value, $ttl);

        return $value;
    }

    /**
     * Remove an item from cache
     */
    public function forget(string $key): bool
    {
        return $this->driver()->forget($key);
    }

    /**
     * Clear all cache
     */
    public function flush(): bool
    {
        return $this->driver()->flush();
    }

    /**
     * Check if an item exists in cache
     */
    public function has(string $key): bool
    {
        return $this->driver()->has($key);
    }
}

