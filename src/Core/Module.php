<?php

namespace IsekaiPHP\Core;

use IsekaiPHP\Http\Router;

/**
 * Base Module Class
 * 
 * All modules must extend this class and implement the required methods.
 */
abstract class Module
{
    protected string $path;
    protected array $manifest;
    protected bool $enabled = true;

    /**
     * Create a new module instance
     */
    public function __construct(string $path, array $manifest)
    {
        $this->path = $path;
        $this->manifest = $manifest;
    }

    /**
     * Get the module path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the module manifest
     */
    public function getManifest(): array
    {
        return $this->manifest;
    }

    /**
     * Get module name
     */
    public function getName(): string
    {
        return $this->manifest['name'] ?? '';
    }

    /**
     * Get display name
     */
    public function getDisplayName(): string
    {
        return $this->manifest['display_name'] ?? $this->getName();
    }

    /**
     * Get module version
     */
    public function getVersion(): string
    {
        return $this->manifest['version'] ?? '1.0.0';
    }

    /**
     * Check if module is enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Enable the module
     */
    public function enable(): void
    {
        $this->enabled = true;
    }

    /**
     * Disable the module
     */
    public function disable(): void
    {
        $this->enabled = false;
    }

    /**
     * Register the module
     * 
     * Called when the module is first loaded.
     * Override this to register services, routes, etc.
     */
    public function register(Container $container): void
    {
        // Override in child classes
    }

    /**
     * Boot the module
     * 
     * Called after all modules are registered.
     * Override this for initialization that depends on other modules.
     */
    public function boot(Container $container): void
    {
        // Override in child classes
    }

    /**
     * Get routes file path
     */
    public function getRoutesPath(string $type = 'web'): ?string
    {
        $routesPath = $this->path . '/routes/' . $type . '.php';
        return file_exists($routesPath) ? $routesPath : null;
    }

    /**
     * Get views path
     */
    public function getViewsPath(): string
    {
        $viewsPath = $this->path . '/views';
        return is_dir($viewsPath) ? $viewsPath : $this->path;
    }

    /**
     * Get migrations path
     */
    public function getMigrationsPath(): string
    {
        $migrationsPath = $this->path . '/migrations';
        return is_dir($migrationsPath) ? $migrationsPath : $this->path;
    }

    /**
     * Get assets path
     */
    public function getAssetsPath(): string
    {
        $assetsPath = $this->path . '/assets';
        return is_dir($assetsPath) ? $assetsPath : $this->path;
    }

    /**
     * Get config path
     */
    public function getConfigPath(): string
    {
        $configPath = $this->path . '/config';
        return is_dir($configPath) ? $configPath : $this->path;
    }

    /**
     * Get module namespace
     */
    public function getNamespace(): string
    {
        return $this->manifest['extra']['module']['namespace'] ?? '';
    }

    /**
     * Get module class name
     */
    public function getModuleClass(): string
    {
        return $this->manifest['extra']['module']['class'] ?? '';
    }

    /**
     * Get service provider class name
     */
    public function getServiceProviderClass(): ?string
    {
        return $this->manifest['extra']['module']['service_provider'] ?? null;
    }
}

