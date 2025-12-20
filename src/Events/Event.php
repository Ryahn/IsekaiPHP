<?php

namespace IsekaiPHP\Events;

/**
 * Base Event Class
 */
class Event
{
    /**
     * Event data
     */
    protected array $data = [];

    /**
     * Create a new event instance
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Get event data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get a specific data value
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set a data value
     */
    public function set(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Check if data key exists
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }
}
