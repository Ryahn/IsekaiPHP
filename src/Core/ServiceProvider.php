<?php

namespace IsekaiPHP\Core;

/**
 * Service Provider Interface
 * 
 * Service providers are responsible for binding services into the container
 * and performing any initialization tasks.
 */
interface ServiceProvider
{
    /**
     * Register services into the container
     * 
     * This method is called during module registration.
     * Use this to bind services, singletons, etc.
     */
    public function register(Container $container): void;

    /**
     * Boot services after all modules are registered
     * 
     * This method is called after all modules have been registered.
     * Use this for initialization that depends on other modules.
     */
    public function boot(Container $container): void;
}

