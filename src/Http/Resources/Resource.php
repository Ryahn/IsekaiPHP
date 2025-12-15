<?php

namespace IsekaiPHP\Http\Resources;

/**
 * API Resource
 * 
 * Transforms data for API responses.
 */
abstract class Resource
{
    protected $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    /**
     * Transform the resource into an array
     */
    abstract public function toArray(): array;

    /**
     * Create a new resource instance
     */
    public static function make($resource): static
    {
        return new static($resource);
    }

    /**
     * Create a collection of resources
     */
    public static function collection($resources): array
    {
        return array_map(function ($resource) {
            return static::make($resource)->toArray();
        }, is_array($resources) ? $resources : iterator_to_array($resources));
    }

    /**
     * Get resource value
     */
    protected function get($key, $default = null)
    {
        if (is_array($this->resource)) {
            return $this->resource[$key] ?? $default;
        }

        if (is_object($this->resource)) {
            return $this->resource->$key ?? $default;
        }

        return $default;
    }

    /**
     * Check if resource has key
     */
    protected function has($key): bool
    {
        if (is_array($this->resource)) {
            return isset($this->resource[$key]);
        }

        if (is_object($this->resource)) {
            return isset($this->resource->$key);
        }

        return false;
    }
}

