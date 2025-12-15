<?php

namespace IsekaiPHP\Session;

use IsekaiPHP\Core\Config;

/**
 * Session Manager
 * 
 * Manages session operations with multiple drivers.
 */
class SessionManager
{
    protected array $config;
    protected string $driver;
    protected SessionInterface $handler;

    public function __construct(array $config = [])
    {
        $this->config = $config ?: Config::get('session', []);
        $this->driver = $this->config['driver'] ?? 'file';
        $this->handler = $this->createHandler();
        
        $this->start();
    }

    /**
     * Create session handler
     */
    protected function createHandler(): SessionInterface
    {
        $config = $this->config['stores'][$this->driver] ?? [];

        return match ($this->driver) {
            'file' => new Drivers\FileSession($config),
            'database' => new Drivers\DatabaseSession($config),
            'cookie' => new Drivers\CookieSession($config),
            default => new Drivers\FileSession($config),
        };
    }

    /**
     * Start session
     */
    protected function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $this->handler->start();
        }
    }

    /**
     * Get a session value
     */
    public function get(string $key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Set a session value
     */
    public function set(string $key, $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Check if session key exists
     */
    public function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove a session value
     */
    public function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Get all session data
     */
    public function all(): array
    {
        return $_SESSION;
    }

    /**
     * Clear all session data
     */
    public function flush(): void
    {
        $_SESSION = [];
    }

    /**
     * Flash a value to the session
     */
    public function flash(string $key, $value): void
    {
        $this->set('_flash.new.' . $key, $value);
    }

    /**
     * Get and remove a flashed value
     */
    public function pull(string $key, $default = null)
    {
        $value = $this->get('_flash.old.' . $key, $default);
        $this->forget('_flash.old.' . $key);
        return $value;
    }

    /**
     * Reflash all flash data
     */
    public function reflash(): void
    {
        $old = [];
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, '_flash.old.') === 0) {
                $newKey = str_replace('_flash.old.', '_flash.new.', $key);
                $old[$newKey] = $value;
            }
        }
        $_SESSION = array_merge($_SESSION, $old);
    }

    /**
     * Age flash data (move new to old)
     */
    public function ageFlashData(): void
    {
        $new = [];
        $old = [];
        
        foreach ($_SESSION as $key => $value) {
            if (strpos($key, '_flash.new.') === 0) {
                $oldKey = str_replace('_flash.new.', '_flash.old.', $key);
                $old[$oldKey] = $value;
                unset($_SESSION[$key]);
            } elseif (strpos($key, '_flash.old.') === 0) {
                unset($_SESSION[$key]);
            }
        }
        
        $_SESSION = array_merge($_SESSION, $old);
    }

    /**
     * Regenerate session ID
     */
    public function regenerate(bool $deleteOld = false): bool
    {
        return session_regenerate_id($deleteOld);
    }

    /**
     * Destroy session
     */
    public function destroy(): void
    {
        session_destroy();
        $_SESSION = [];
    }

    /**
     * Get session ID
     */
    public function getId(): string
    {
        return session_id();
    }
}

