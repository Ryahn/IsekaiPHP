<?php

namespace IsekaiPHP\Events;

/**
 * Event Dispatcher
 * 
 * Handles event publishing and listener registration.
 */
class EventDispatcher
{
    protected array $listeners = [];
    protected array $wildcards = [];

    /**
     * Register an event listener
     */
    public function listen(string $event, $listener): void
    {
        if (strpos($event, '*') !== false) {
            $this->wildcards[$event][] = $listener;
        } else {
            $this->listeners[$event][] = $listener;
        }
    }

    /**
     * Register multiple event listeners
     */
    public function subscribe($subscriber): void
    {
        if (is_string($subscriber)) {
            $subscriber = new $subscriber();
        }

        if (method_exists($subscriber, 'subscribe')) {
            $subscriber->subscribe($this);
        }
    }

    /**
     * Fire an event
     */
    public function dispatch(string $event, $payload = []): ?array
    {
        $responses = [];

        // Convert payload to Event instance if it's an array
        if (is_array($payload) && !($payload instanceof Event)) {
            $payload = new Event($payload);
        }

        // Get listeners for this event
        $listeners = $this->getListeners($event);

        foreach ($listeners as $listener) {
            $response = $this->callListener($listener, $event, $payload);
            
            if ($response !== null) {
                $responses[] = $response;
            }
        }

        return !empty($responses) ? $responses : null;
    }

    /**
     * Get listeners for an event
     */
    protected function getListeners(string $event): array
    {
        $listeners = $this->listeners[$event] ?? [];

        // Add wildcard listeners
        foreach ($this->wildcards as $pattern => $wildcardListeners) {
            if ($this->matchesWildcard($pattern, $event)) {
                $listeners = array_merge($listeners, $wildcardListeners);
            }
        }

        return $listeners;
    }

    /**
     * Check if event matches wildcard pattern
     */
    protected function matchesWildcard(string $pattern, string $event): bool
    {
        // Convert wildcard pattern to regex
        $regex = str_replace(['\\*', '\\?'], ['.*', '.'], preg_quote($pattern, '/'));
        return preg_match('/^' . $regex . '$/', $event) === 1;
    }

    /**
     * Call a listener
     */
    protected function callListener($listener, string $event, $payload)
    {
        if (is_string($listener)) {
            return $this->callClassListener($listener, $event, $payload);
        }

        if (is_callable($listener)) {
            return call_user_func($listener, $payload, $event);
        }

        return null;
    }

    /**
     * Call a class-based listener
     */
    protected function callClassListener(string $listener, string $event, $payload)
    {
        if (strpos($listener, '@') !== false) {
            [$class, $method] = explode('@', $listener, 2);
        } else {
            $class = $listener;
            $method = 'handle';
        }

        if (!class_exists($class)) {
            return null;
        }

        $instance = new $class();

        if (method_exists($instance, $method)) {
            return $instance->$method($payload, $event);
        }

        return null;
    }

    /**
     * Remove a listener
     */
    public function forget(string $event): void
    {
        unset($this->listeners[$event]);
        unset($this->wildcards[$event]);
    }

    /**
     * Remove all listeners
     */
    public function flush(): void
    {
        $this->listeners = [];
        $this->wildcards = [];
    }

    /**
     * Get all registered events
     */
    public function getEvents(): array
    {
        return array_merge(array_keys($this->listeners), array_keys($this->wildcards));
    }

    /**
     * Check if event has listeners
     */
    public function hasListeners(string $event): bool
    {
        return isset($this->listeners[$event]) || $this->hasWildcardListeners($event);
    }

    /**
     * Check if event has wildcard listeners
     */
    protected function hasWildcardListeners(string $event): bool
    {
        foreach (array_keys($this->wildcards) as $pattern) {
            if ($this->matchesWildcard($pattern, $event)) {
                return true;
            }
        }

        return false;
    }
}

