<?php

namespace IsekaiPHP\Storage;

use IsekaiPHP\Core\Config;

/**
 * Storage Manager
 *
 * Manages file storage operations with multiple drivers.
 */
class StorageManager
{
    protected array $config;
    protected array $disks = [];
    protected string $defaultDisk;
    protected array $customDrivers = [];

    public function __construct(array $config = [])
    {
        $this->config = $config ?: Config::get('storage', []);
        $this->defaultDisk = $this->config['default'] ?? 'local';
    }

    /**
     * Register a custom storage driver
     */
    public function extend(string $driver, callable $callback): void
    {
        $this->customDrivers[$driver] = $callback;
    }

    /**
     * Get a disk instance
     */
    public function disk(?string $disk = null): StorageInterface
    {
        $disk = $disk ?? $this->defaultDisk;

        if (! isset($this->disks[$disk])) {
            $this->disks[$disk] = $this->createDisk($disk);
        }

        return $this->disks[$disk];
    }

    /**
     * Create a disk instance
     */
    protected function createDisk(string $disk): StorageInterface
    {
        $config = $this->config['disks'][$disk] ?? [];
        $driver = $config['driver'] ?? 'local';

        // Check for custom driver first (registered by modules)
        if (isset($this->customDrivers[$driver])) {
            $instance = call_user_func($this->customDrivers[$driver], $config);
            if ($instance instanceof StorageInterface) {
                return $instance;
            }
        }

        // Use built-in drivers
        return match ($driver) {
            'local' => new Drivers\LocalStorage($config),
            's3' => new Drivers\S3Storage($config),
            default => new Drivers\LocalStorage($config),
        };
    }

    /**
     * Store a file
     */
    public function put(string $path, $contents, ?string $disk = null): bool
    {
        return $this->disk($disk)->put($path, $contents);
    }

    /**
     * Get file contents
     */
    public function get(string $path, ?string $disk = null): ?string
    {
        return $this->disk($disk)->get($path);
    }

    /**
     * Check if file exists
     */
    public function exists(string $path, ?string $disk = null): bool
    {
        return $this->disk($disk)->exists($path);
    }

    /**
     * Delete a file
     */
    public function delete(string $path, ?string $disk = null): bool
    {
        return $this->disk($disk)->delete($path);
    }

    /**
     * Copy a file
     */
    public function copy(string $from, string $to, ?string $disk = null): bool
    {
        return $this->disk($disk)->copy($from, $to);
    }

    /**
     * Move a file
     */
    public function move(string $from, string $to, ?string $disk = null): bool
    {
        return $this->disk($disk)->move($from, $to);
    }

    /**
     * Get file size
     */
    public function size(string $path, ?string $disk = null): int
    {
        return $this->disk($disk)->size($path);
    }

    /**
     * Get file URL
     */
    public function url(string $path, ?string $disk = null): string
    {
        return $this->disk($disk)->url($path);
    }
}
