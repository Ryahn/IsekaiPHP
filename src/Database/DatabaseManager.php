<?php

namespace IsekaiPHP\Database;

use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use Illuminate\Container\Container;

class DatabaseManager
{
    protected static ?Capsule $capsule = null;

    /**
     * Initialize Eloquent
     */
    public static function initialize(array $config): void
    {
        if (self::$capsule !== null) {
            return;
        }

        self::$capsule = new Capsule();

        $default = $config['default'] ?? 'mysql';
        $connection = $config['connections'][$default] ?? [];

        self::$capsule->addConnection($connection);

        // Set event dispatcher
        self::$capsule->setEventDispatcher(new Dispatcher(new Container()));

        // Make this Capsule instance available globally
        self::$capsule->setAsGlobal();

        // Boot Eloquent
        self::$capsule->bootEloquent();
    }

    /**
     * Get the Capsule instance
     */
    public static function getCapsule(): ?Capsule
    {
        return self::$capsule;
    }

    /**
     * Get database connection
     */
    public static function connection(?string $name = null)
    {
        return self::$capsule->getConnection($name);
    }
}
