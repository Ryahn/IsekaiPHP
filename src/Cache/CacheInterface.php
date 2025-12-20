<?php

namespace IsekaiPHP\Cache;

/**
 * Cache Interface
 */
interface CacheInterface
{
    /**
     * Get an item from cache
     */
    public function get(string $key, $default = null);

    /**
     * Store an item in cache
     */
    public function put(string $key, $value, ?int $ttl = null): bool;

    /**
     * Store an item in cache if it doesn't exist
     */
    public function add(string $key, $value, ?int $ttl = null): bool;

    /**
     * Remove an item from cache
     */
    public function forget(string $key): bool;

    /**
     * Clear all cache
     */
    public function flush(): bool;

    /**
     * Check if an item exists in cache
     */
    public function has(string $key): bool;
}
