<?php

namespace IsekaiPHP\Session\Drivers;

use IsekaiPHP\Session\SessionInterface;

/**
 * File Session Driver
 */
class FileSession implements SessionInterface
{
    protected string $path;
    protected int $lifetime;

    public function __construct(array $config = [])
    {
        $this->path = $config['path'] ?? sys_get_temp_dir() . '/sessions';
        $this->lifetime = $config['lifetime'] ?? 7200; // 2 hours

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }

        ini_set('session.save_path', $this->path);
        ini_set('session.gc_maxlifetime', $this->lifetime);
    }

    /**
     * Start the session
     */
    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}

