<?php

namespace TestModule;

use IsekaiPHP\Core\ServiceProvider as BaseServiceProvider;
use IsekaiPHP\Core\Container;

class ServiceProvider implements BaseServiceProvider
{
    /**
     * Register services
     */
    public function register(Container $container): void
    {
        // Bind services here
    }

    /**
     * Boot services
     */
    public function boot(Container $container): void
    {
        // Boot services here
    }
}
