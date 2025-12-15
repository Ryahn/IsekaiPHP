<?php

namespace IsekaiPHP\Session\Drivers;

use IsekaiPHP\Session\SessionInterface;
use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * Database Session Driver
 */
class DatabaseSession implements SessionInterface
{
    protected string $table;
    protected int $lifetime;

    public function __construct(array $config = [])
    {
        $this->table = $config['table'] ?? 'sessions';
        $this->lifetime = $config['lifetime'] ?? 7200;

        // Set custom session save handler
        session_set_save_handler(
            [$this, 'open'],
            [$this, 'close'],
            [$this, 'read'],
            [$this, 'write'],
            [$this, 'destroy'],
            [$this, 'gc']
        );
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

    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read($id): string
    {
        $session = Capsule::table($this->table)
            ->where('id', $id)
            ->where('last_activity', '>', time() - $this->lifetime)
            ->first();

        return $session ? $session->payload : '';
    }

    public function write($id, $data): bool
    {
        return Capsule::table($this->table)->updateOrInsert(
            ['id' => $id],
            [
                'payload' => $data,
                'last_activity' => time(),
            ]
        );
    }

    public function destroy($id): bool
    {
        return Capsule::table($this->table)->where('id', $id)->delete() > 0;
    }

    public function gc($maxLifetime): int
    {
        return Capsule::table($this->table)
            ->where('last_activity', '<', time() - $maxLifetime)
            ->delete();
    }
}

