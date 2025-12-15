<?php

namespace IsekaiPHP\Cache\Drivers;

use IsekaiPHP\Cache\CacheInterface;

/**
 * Redis Cache Driver
 */
class RedisCache implements CacheInterface
{
    protected ?\Redis $redis = null;
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;

        if (extension_loaded('redis')) {
            try {
                $this->redis = new \Redis();
                $host = $config['host'] ?? '127.0.0.1';
                $port = $config['port'] ?? 6379;
                $timeout = $config['timeout'] ?? 0;
                
                if (!$this->redis->connect($host, $port, $timeout)) {
                    $this->redis = null;
                }

                if (isset($config['password'])) {
                    $this->redis->auth($config['password']);
                }

                if (isset($config['database'])) {
                    $this->redis->select($config['database']);
                }
            } catch (\Exception $e) {
                $this->redis = null;
            }
        }
    }

    /**
     * Check if Redis is available
     */
    protected function isAvailable(): bool
    {
        return $this->redis !== null;
    }

    /**
     * Get an item from cache
     */
    public function get(string $key, $default = null)
    {
        if (!$this->isAvailable()) {
            return $default;
        }

        $value = $this->redis->get($key);

        if ($value === false) {
            return $default;
        }

        return unserialize($value);
    }

    /**
     * Store an item in cache
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        $serialized = serialize($value);

        if ($ttl !== null) {
            return $this->redis->setex($key, $ttl, $serialized);
        }

        return $this->redis->set($key, $serialized);
    }

    /**
     * Store an item in cache if it doesn't exist
     */
    public function add(string $key, $value, ?int $ttl = null): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

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
        if (!$this->isAvailable()) {
            return false;
        }

        return $this->redis->del($key) > 0;
    }

    /**
     * Clear all cache
     */
    public function flush(): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return $this->redis->flushDB();
    }

    /**
     * Check if an item exists in cache
     */
    public function has(string $key): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        return $this->redis->exists($key) > 0;
    }
}

