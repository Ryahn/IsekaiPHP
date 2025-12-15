<?php

namespace IsekaiPHP\Cache\Drivers;

use IsekaiPHP\Cache\CacheInterface;

/**
 * Array Cache Driver
 * 
 * In-memory cache for testing or single-request scenarios.
 */
class ArrayCache implements CacheInterface
{
    protected array $cache = [];
    protected array $expires = [];

    public function __construct(array $config = [])
    {
        // No configuration needed for array cache
    }

    /**
     * Get an item from cache
     */
    public function get(string $key, $default = null)
    {
        if (!$this->has($key)) {
            return $default;
        }

        return $this->cache[$key];
    }

    /**
     * Store an item in cache
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        $this->cache[$key] = $value;

        if ($ttl !== null) {
            $this->expires[$key] = time() + $ttl;
        } else {
            unset($this->expires[$key]);
        }

        return true;
    }

    /**
     * Store an item in cache if it doesn't exist
     */
    public function add(string $key, $value, ?int $ttl = null): bool
    {
        if ($this->has($key)) {
            return false;
        }

        return $this->put($key, $value, $ttl);
    }

    /**
     * Remove an item from cache
     */
    public function forget(string $key): bool
    {
        unset($this->cache[$key]);
        unset($this->expires[$key]);

        return true;
    }

    /**
     * Clear all cache
     */
    public function flush(): bool
    {
        $this->cache = [];
        $this->expires = [];

        return true;
    }

    /**
     * Check if an item exists in cache
     */
    public function has(string $key): bool
    {
        if (!isset($this->cache[$key])) {
            return false;
        }

        // Check if expired
        if (isset($this->expires[$key]) && $this->expires[$key] < time()) {
            $this->forget($key);
            return false;
        }

        return true;
    }
}

