<?php

namespace IsekaiPHP\Core;

use IsekaiPHP\Http\Router;

/**
 * Module Manager
 * 
 * Handles module discovery, loading, and lifecycle management.
 */
class ModuleManager
{
    protected Container $container;
    protected string $basePath;
    protected array $modules = [];
    protected array $loadedModules = [];

    public function __construct(Container $container, string $basePath)
    {
        $this->container = $container;
        $this->basePath = $basePath;
    }

    /**
     * Discover and load all modules
     */
    public function discover(): void
    {
        $modulesPath = $this->basePath . '/modules';
        
        if (!is_dir($modulesPath)) {
            return;
        }

        $directories = array_filter(glob($modulesPath . '/*'), 'is_dir');
        
        foreach ($directories as $modulePath) {
            $moduleJsonPath = $modulePath . '/module.json';
            
            if (!file_exists($moduleJsonPath)) {
                continue;
            }

            $manifest = json_decode(file_get_contents($moduleJsonPath), true);
            
            if (!$manifest || !isset($manifest['name'])) {
                continue;
            }

            // Check if module is enabled in config
            $enabled = $this->isModuleEnabled($manifest['name']);
            
            if (!$enabled) {
                continue;
            }

            $this->modules[$manifest['name']] = [
                'path' => $modulePath,
                'manifest' => $manifest,
            ];
        }

        // Resolve module dependencies and sort by dependency order
        $this->modules = $this->resolveDependencies($this->modules);
    }

    /**
     * Load and register all discovered modules
     */
    public function loadModules(): void
    {
        // First, ensure module dependencies are installed
        foreach ($this->modules as $name => $moduleInfo) {
            $this->ensureComposerDependencies($moduleInfo['path']);
        }

        // Load module classes
        foreach ($this->modules as $name => $moduleInfo) {
            $this->loadModule($name, $moduleInfo);
        }

        // Boot all modules
        foreach ($this->loadedModules as $module) {
            if ($module->isEnabled()) {
                $module->boot($this->container);
            }
        }

        // Register module extensions (mail drivers, cache drivers, etc.)
        $this->registerModuleExtensions();
    }

    /**
     * Load a single module
     */
    protected function loadModule(string $name, array $moduleInfo): void
    {
        $manifest = $moduleInfo['manifest'];
        $modulePath = $moduleInfo['path'];

        // Get module class from manifest
        $moduleClass = $manifest['extra']['module']['class'] ?? null;
        
        if (!$moduleClass || !class_exists($moduleClass)) {
            // Try to autoload from module path
            $namespace = $manifest['extra']['module']['namespace'] ?? '';
            if ($namespace) {
                // Try to find Module.php in src directory first
                $moduleFile = $modulePath . '/src/Module.php';
                if (file_exists($moduleFile)) {
                    require_once $moduleFile;
                    $moduleClass = $namespace . '\\Module';
                } else {
                    // Fallback to root Module.php
                    $moduleFile = $modulePath . '/Module.php';
                    if (file_exists($moduleFile)) {
                        require_once $moduleFile;
                        $moduleClass = $namespace . '\\Module';
                    }
                }
            }
        }

        if (!$moduleClass || !class_exists($moduleClass)) {
            // Create a default module instance
            $module = new class($modulePath, $manifest) extends Module {};
        } else {
            $module = new $moduleClass($modulePath, $manifest);
        }

        // Load service provider if exists
        $serviceProviderClass = $module->getServiceProviderClass();
        if ($serviceProviderClass) {
            // Try to load from src directory
            $serviceProviderFile = $modulePath . '/src/ServiceProvider.php';
            if (file_exists($serviceProviderFile) && !class_exists($serviceProviderClass)) {
                require_once $serviceProviderFile;
            }
            
            if (class_exists($serviceProviderClass)) {
                $serviceProvider = new $serviceProviderClass();
                if ($serviceProvider instanceof ServiceProvider) {
                    $serviceProvider->register($this->container);
                }
            }
        }

        // Register module
        if ($module->isEnabled()) {
            $module->register($this->container);
        }

        $this->loadedModules[$name] = $module;
    }

    /**
     * Register module routes
     */
    public function registerRoutes(Router $router): void
    {
        foreach ($this->loadedModules as $module) {
            if (!$module->isEnabled()) {
                continue;
            }

            // Register web routes
            $webRoutesPath = $module->getRoutesPath('web');
            if ($webRoutesPath) {
                $this->registerModuleRoutes($router, $webRoutesPath, $module);
            }

            // Register API routes
            $apiRoutesPath = $module->getRoutesPath('api');
            if ($apiRoutesPath) {
                $this->registerModuleRoutes($router, $apiRoutesPath, $module, 'api');
            }
        }
    }

    /**
     * Register routes from a module route file
     */
    protected function registerModuleRoutes(Router $router, string $routesPath, Module $module, string $type = 'web'): void
    {
        $moduleName = $module->getName();
        $prefix = $this->getModuleRoutePrefix($moduleName, $type);

        $router->group(['prefix' => $prefix], function ($r) use ($routesPath) {
            // The route file expects $router to be available
            $router = $r;
            require $routesPath;
        });
    }

    /**
     * Get route prefix for a module
     */
    protected function getModuleRoutePrefix(string $moduleName, string $type = 'web'): string
    {
        $config = Config::get('modules', []);
        $prefix = $config['route_prefix'] ?? 'modules';
        
        if ($type === 'api') {
            return '/' . trim($prefix, '/') . '/' . $moduleName;
        }
        
        return '/' . trim($prefix, '/') . '/' . $moduleName;
    }

    /**
     * Register module view namespaces
     */
    public function registerViews(): void
    {
        foreach ($this->loadedModules as $module) {
            if (!$module->isEnabled()) {
                continue;
            }

            $moduleName = $module->getName();
            $viewsPath = $module->getViewsPath();
            
            if (is_dir($viewsPath)) {
                $factory = \IsekaiPHP\Core\View::factory();
                if ($factory) {
                    $finder = $factory->getFinder();
                    // Add namespace for module views (e.g., module::view-name)
                    $finder->addNamespace($moduleName, $viewsPath);
                }
            }
        }
    }

    /**
     * Get all loaded modules
     */
    public function getModules(): array
    {
        return $this->loadedModules;
    }

    /**
     * Get a specific module
     */
    public function getModule(string $name): ?Module
    {
        return $this->loadedModules[$name] ?? null;
    }

    /**
     * Check if a module is enabled in configuration
     */
    protected function isModuleEnabled(string $moduleName): bool
    {
        $config = Config::get('modules', []);
        $disabled = $config['disabled'] ?? [];
        
        return !in_array($moduleName, $disabled);
    }

    /**
     * Ensure Composer dependencies are installed for a module
     */
    protected function ensureComposerDependencies(string $modulePath): void
    {
        $composerJsonPath = $modulePath . '/composer.json';
        $vendorPath = $modulePath . '/vendor';
        
        if (!file_exists($composerJsonPath)) {
            return;
        }

        // Check if vendor directory exists and has autoload.php
        if (!is_dir($vendorPath) || !file_exists($vendorPath . '/autoload.php')) {
            // Run composer install in the module directory
            $this->runComposerInstall($modulePath);
        } else {
            // Include the module's autoloader
            require_once $vendorPath . '/autoload.php';
        }
    }

    /**
     * Run composer install in a module directory
     */
    protected function runComposerInstall(string $modulePath): void
    {
        $command = sprintf(
            'cd %s && composer install --no-interaction --quiet',
            escapeshellarg($modulePath)
        );
        
        exec($command . ' 2>&1', $output, $returnVar);
        
        // Include autoloader if it exists after install
        $vendorPath = $modulePath . '/vendor/autoload.php';
        if (file_exists($vendorPath)) {
            require_once $vendorPath;
        }
    }

    /**
     * Get module migrations paths
     */
    public function getMigrationPaths(): array
    {
        $paths = [];
        
        foreach ($this->loadedModules as $module) {
            if (!$module->isEnabled()) {
                continue;
            }

            $migrationsPath = $module->getMigrationsPath();
            if (is_dir($migrationsPath)) {
                $paths[] = $migrationsPath;
            }
        }

        return $paths;
    }

    /**
     * Resolve module dependencies and return sorted modules
     */
    protected function resolveDependencies(array $modules): array
    {
        $sorted = [];
        $visited = [];
        $visiting = [];

        foreach ($modules as $name => $moduleInfo) {
            $this->visitModule($name, $modules, $sorted, $visited, $visiting);
        }

        return $sorted;
    }

    /**
     * Visit a module and its dependencies (topological sort)
     */
    protected function visitModule(string $name, array $modules, array &$sorted, array &$visited, array &$visiting): void
    {
        if (isset($visited[$name])) {
            return;
        }

        if (isset($visiting[$name])) {
            throw new \Exception("Circular dependency detected involving module: {$name}");
        }

        $visiting[$name] = true;

        // Get dependencies
        $manifest = $modules[$name]['manifest'] ?? [];
        $dependencies = $manifest['requires']['modules'] ?? [];

        foreach ($dependencies as $depName => $version) {
            if (!isset($modules[$depName])) {
                throw new \Exception("Module {$name} requires {$depName} which is not installed or disabled");
            }
            $this->visitModule($depName, $modules, $sorted, $visited, $visiting);
        }

        unset($visiting[$name]);
        $visited[$name] = true;
        $sorted[$name] = $modules[$name];
    }

    /**
     * Load module configuration files
     */
    public function loadModuleConfigs(): void
    {
        foreach ($this->loadedModules as $module) {
            if (!$module->isEnabled()) {
                continue;
            }

            $configPath = $module->getConfigPath();
            if (!is_dir($configPath)) {
                continue;
            }

            $configFiles = glob($configPath . '/*.php');
            foreach ($configFiles as $configFile) {
                $configKey = basename($configFile, '.php');
                $key = 'modules.' . $module->getName() . '.' . $configKey;
                $config = require $configFile;
                Config::set($key, $config);
            }
        }
    }

    /**
     * Get module configuration
     */
    public function getModuleConfig(string $moduleName, ?string $key = null, $default = null)
    {
        $configKey = 'modules.' . $moduleName;
        if ($key) {
            $configKey .= '.' . $key;
        }

        return Config::get($configKey, $default);
    }

    /**
     * Publish module assets to public directory
     */
    public function publishAssets(string $moduleName, bool $force = false): bool
    {
        $module = $this->getModule($moduleName);
        if (!$module) {
            return false;
        }

        $assetsPath = $module->getAssetsPath();
        if (!is_dir($assetsPath)) {
            return false;
        }

        $publicPath = $this->basePath . '/public/modules/' . $moduleName;
        
        if (is_dir($publicPath) && !$force) {
            return false; // Already published
        }

        if (!is_dir($publicPath)) {
            mkdir($publicPath, 0755, true);
        }

        $this->copyDirectory($assetsPath, $publicPath);

        return true;
    }

    /**
     * Copy directory recursively
     */
    protected function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $destPath = $destination . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            
            if ($item->isDir()) {
                if (!is_dir($destPath)) {
                    mkdir($destPath, 0755, true);
                }
            } else {
                copy($item, $destPath);
            }
        }
    }

    /**
     * Register module extensions (mail drivers, cache drivers, storage drivers)
     */
    protected function registerModuleExtensions(): void
    {
        $app = $this->container->make(\IsekaiPHP\Core\Application::class);
        
        foreach ($this->loadedModules as $module) {
            if (!$module->isEnabled()) {
                continue;
            }

            $manifest = $module->getManifest();
            $extensions = $manifest['extensions'] ?? [];

            // Register mail drivers
            if (isset($extensions['mail_drivers']) && is_array($extensions['mail_drivers'])) {
                $mailManager = $app->getMailManager();
                foreach ($extensions['mail_drivers'] as $driverName => $driverClass) {
                    $mailManager->extend($driverName, function ($config) use ($driverClass) {
                        return new $driverClass($config);
                    });
                }
            }

            // Register cache drivers
            if (isset($extensions['cache_drivers']) && is_array($extensions['cache_drivers'])) {
                $cacheManager = $app->getCacheManager();
                foreach ($extensions['cache_drivers'] as $driverName => $driverClass) {
                    $cacheManager->extend($driverName, function ($config) use ($driverClass) {
                        return new $driverClass($config);
                    });
                }
            }

            // Register storage drivers
            if (isset($extensions['storage_drivers']) && is_array($extensions['storage_drivers'])) {
                $storageManager = $app->getStorageManager();
                foreach ($extensions['storage_drivers'] as $driverName => $driverClass) {
                    $storageManager->extend($driverName, function ($config) use ($driverClass) {
                        return new $driverClass($config);
                    });
                }
            }
        }
    }

    /**
     * Register module middleware
     */
    public function registerMiddleware(Router $router): void
    {
        foreach ($this->loadedModules as $module) {
            if (!$module->isEnabled()) {
                continue;
            }

            $manifest = $module->getManifest();
            $middleware = $manifest['middleware'] ?? [];

            // Register global middleware
            if (isset($middleware['global']) && is_array($middleware['global'])) {
                foreach ($middleware['global'] as $middlewareClass) {
                    // Check if middleware class exists (might be in module's namespace)
                    if (class_exists($middlewareClass)) {
                        $router->middleware($middlewareClass);
                    } else {
                        // Try to find in module namespace
                        $namespace = $module->getNamespace();
                        $fullClass = $namespace . '\\' . $middlewareClass;
                        if (class_exists($fullClass)) {
                            $router->middleware($fullClass);
                        }
                    }
                }
            }

            // Route-specific middleware is handled in route files
        }
    }

    /**
     * Enable a module
     *
     * @param string $moduleName
     * @return bool
     */
    public function enableModule(string $moduleName): bool
    {
        $config = Config::get('modules', []);
        $disabled = $config['disabled'] ?? [];
        
        // Remove from disabled list
        $disabled = array_filter($disabled, function ($name) use ($moduleName) {
            return $name !== $moduleName;
        });
        
        $config['disabled'] = array_values($disabled);
        return $this->saveModuleConfig($config);
    }

    /**
     * Disable a module
     *
     * @param string $moduleName
     * @return bool
     */
    public function disableModule(string $moduleName): bool
    {
        $config = Config::get('modules', []);
        $disabled = $config['disabled'] ?? [];
        
        // Add to disabled list if not already there
        if (!in_array($moduleName, $disabled)) {
            $disabled[] = $moduleName;
        }
        
        $config['disabled'] = $disabled;
        return $this->saveModuleConfig($config);
    }

    /**
     * Disable all modules
     *
     * @return bool
     */
    public function disableAllModules(): bool
    {
        $config = Config::get('modules', []);
        $allModules = array_keys($this->loadedModules);
        
        $config['disabled'] = $allModules;
        return $this->saveModuleConfig($config);
    }

    /**
     * Get module status (enabled/disabled)
     *
     * @param string $moduleName
     * @return bool
     */
    public function getModuleStatus(string $moduleName): bool
    {
        return $this->isModuleEnabled($moduleName);
    }

    /**
     * Save module configuration to file
     *
     * @param array|null $config
     * @return bool
     */
    public function saveModuleConfig(?array $config = null): bool
    {
        if ($config === null) {
            $config = Config::get('modules', []);
        }

        $configPath = $this->basePath . '/config/modules.php';
        
        // Update Config
        Config::set('modules', $config);
        
        // Write to file
        $content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
        
        return file_put_contents($configPath, $content) !== false;
    }

    /**
     * Register admin routes and collect admin menu items from modules
     *
     * @param Router $router
     * @return void
     */
    public function registerAdminRoutes(Router $router): void
    {
        foreach ($this->loadedModules as $module) {
            if (!$module->isEnabled()) {
                continue;
            }

            // Collect admin menu items
            $menuItems = $module->getAdminMenuItems();
            if (!empty($menuItems)) {
                AdminMenuRegistry::registerMenuItems($menuItems);
            }

            // Register admin routes
            $module->registerAdminRoutes($this->container, $router);
        }
    }

    /**
     * Get all discovered modules (including disabled ones)
     *
     * @return array
     */
    public function getAllModules(): array
    {
        $modulesPath = $this->basePath . '/modules';
        $modules = [];
        
        if (!is_dir($modulesPath)) {
            return $modules;
        }

        $directories = array_filter(glob($modulesPath . '/*'), 'is_dir');
        
        foreach ($directories as $modulePath) {
            $moduleJsonPath = $modulePath . '/module.json';
            
            if (!file_exists($moduleJsonPath)) {
                continue;
            }

            $manifest = json_decode(file_get_contents($moduleJsonPath), true);
            
            if (!$manifest || !isset($manifest['name'])) {
                continue;
            }

            $moduleName = $manifest['name'];
            $enabled = $this->isModuleEnabled($moduleName);
            
            $modules[$moduleName] = [
                'name' => $moduleName,
                'display_name' => $manifest['display_name'] ?? $moduleName,
                'version' => $manifest['version'] ?? '1.0.0',
                'description' => $manifest['description'] ?? '',
                'enabled' => $enabled,
                'path' => $modulePath,
                'manifest' => $manifest,
            ];
        }

        return $modules;
    }
}

