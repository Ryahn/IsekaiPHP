<?php

namespace IsekaiPHP\Storage\Drivers;

use IsekaiPHP\Storage\StorageInterface;

/**
 * Local File Storage Driver
 */
class LocalStorage implements StorageInterface
{
    protected string $root;
    protected string $url;

    public function __construct(array $config = [])
    {
        $this->root = rtrim($config['root'] ?? storage_path('app'), '/');
        $this->url = $config['url'] ?? '/storage';

        if (!is_dir($this->root)) {
            mkdir($this->root, 0755, true);
        }
    }

    /**
     * Get full path
     */
    protected function path(string $path): string
    {
        $path = ltrim($path, '/');
        return $this->root . '/' . $path;
    }

    /**
     * Store file contents
     */
    public function put(string $path, $contents): bool
    {
        $fullPath = $this->path($path);
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (is_resource($contents)) {
            return file_put_contents($fullPath, stream_get_contents($contents)) !== false;
        }

        return file_put_contents($fullPath, $contents) !== false;
    }

    /**
     * Get file contents
     */
    public function get(string $path): ?string
    {
        $fullPath = $this->path($path);

        if (!file_exists($fullPath)) {
            return null;
        }

        return file_get_contents($fullPath);
    }

    /**
     * Check if file exists
     */
    public function exists(string $path): bool
    {
        return file_exists($this->path($path));
    }

    /**
     * Delete a file
     */
    public function delete(string $path): bool
    {
        $fullPath = $this->path($path);

        if (is_file($fullPath)) {
            return unlink($fullPath);
        }

        if (is_dir($fullPath)) {
            return $this->deleteDirectory($fullPath);
        }

        return false;
    }

    /**
     * Delete directory recursively
     */
    protected function deleteDirectory(string $directory): bool
    {
        if (!is_dir($directory)) {
            return false;
        }

        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $file) {
            $path = $directory . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        return rmdir($directory);
    }

    /**
     * Copy a file
     */
    public function copy(string $from, string $to): bool
    {
        $fromPath = $this->path($from);
        $toPath = $this->path($to);
        $toDir = dirname($toPath);

        if (!is_dir($toDir)) {
            mkdir($toDir, 0755, true);
        }

        return copy($fromPath, $toPath);
    }

    /**
     * Move a file
     */
    public function move(string $from, string $to): bool
    {
        if ($this->copy($from, $to)) {
            return $this->delete($from);
        }

        return false;
    }

    /**
     * Get file size
     */
    public function size(string $path): int
    {
        $fullPath = $this->path($path);
        return file_exists($fullPath) ? filesize($fullPath) : 0;
    }

    /**
     * Get file URL
     */
    public function url(string $path): string
    {
        $path = ltrim($path, '/');
        return rtrim($this->url, '/') . '/' . $path;
    }
}

