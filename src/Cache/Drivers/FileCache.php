<?php

namespace IsekaiPHP\Cache\Drivers;

use IsekaiPHP\Cache\CacheInterface;

/**
 * File Cache Driver
 */
class FileCache implements CacheInterface
{
    protected string $path;
    protected int $defaultTtl;

    public function __construct(array $config = [])
    {
        $this->path = $config['path'] ?? sys_get_temp_dir() . '/isekaiphp_cache';
        $this->defaultTtl = $config['ttl'] ?? 3600;

        if (! is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    /**
     * Get cache file path
     */
    protected function getPath(string $key): string
    {
        $hash = md5($key);

        return $this->path . '/' . substr($hash, 0, 2) . '/' . substr($hash, 2) . '.cache';
    }

    /**
     * Get an item from cache
     */
    public function get(string $key, $default = null)
    {
        $file = $this->getPath($key);

        if (! file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file));

        if ($data === false) {
            return $default;
        }

        // Check if expired
        if (isset($data['expires']) && $data['expires'] < time()) {
            $this->forget($key);

            return $default;
        }

        return $data['value'] ?? $default;
    }

    /**
     * Store an item in cache
     */
    public function put(string $key, $value, ?int $ttl = null): bool
    {
        $file = $this->getPath($key);
        $dir = dirname($file);

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $ttl = $ttl ?? $this->defaultTtl;
        $data = [
            'value' => $value,
            'expires' => time() + $ttl,
        ];

        return file_put_contents($file, serialize($data)) !== false;
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
        $file = $this->getPath($key);

        if (file_exists($file)) {
            return unlink($file);
        }

        return true;
    }

    /**
     * Clear all cache
     */
    public function flush(): bool
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }

        return true;
    }

    /**
     * Check if an item exists in cache
     */
    public function has(string $key): bool
    {
        $file = $this->getPath($key);

        if (! file_exists($file)) {
            return false;
        }

        $data = unserialize(file_get_contents($file));

        if ($data === false) {
            return false;
        }

        // Check if expired
        if (isset($data['expires']) && $data['expires'] < time()) {
            $this->forget($key);

            return false;
        }

        return true;
    }
}
