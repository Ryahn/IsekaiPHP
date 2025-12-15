<?php

namespace IsekaiPHP\Storage;

/**
 * Storage Interface
 */
interface StorageInterface
{
    /**
     * Store file contents
     */
    public function put(string $path, $contents): bool;

    /**
     * Get file contents
     */
    public function get(string $path): ?string;

    /**
     * Check if file exists
     */
    public function exists(string $path): bool;

    /**
     * Delete a file
     */
    public function delete(string $path): bool;

    /**
     * Copy a file
     */
    public function copy(string $from, string $to): bool;

    /**
     * Move a file
     */
    public function move(string $from, string $to): bool;

    /**
     * Get file size
     */
    public function size(string $path): int;

    /**
     * Get file URL
     */
    public function url(string $path): string;
}

