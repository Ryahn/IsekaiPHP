<?php

namespace IsekaiPHP\Core;

use IsekaiPHP\Database\DatabaseManager;
use IsekaiPHP\Http\Request;
use IsekaiPHP\Http\Response;
use IsekaiPHP\Http\Router;
use IsekaiPHP\Core\ModuleManager;
use IsekaiPHP\Events\EventDispatcher;
use IsekaiPHP\Cache\CacheManager;
use IsekaiPHP\Log\Logger;
use IsekaiPHP\Session\SessionManager;
use IsekaiPHP\Storage\StorageManager;
use IsekaiPHP\Mail\MailManager;

class Application
{
    protected Container $container;
    protected string $basePath;
    protected Router $router;
    protected ?ModuleManager $moduleManager = null;
    protected ?EventDispatcher $events = null;
    protected ?CacheManager $cache = null;
    protected ?Logger $logger = null;
    protected ?SessionManager $session = null;
    protected ?StorageManager $storage = null;
    protected ?MailManager $mail = null;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->container = new Container();

        // Set global app instance early so routes can access it
        $GLOBALS['app'] = $this;

        // Load configuration first
        Config::load($this->basePath);

        // Bind core services
        $this->container->singleton(Container::class, function () {
            return $this->container;
        });
        $this->container->singleton(Application::class, function () {
            return $this;
        });
        $this->container->singleton(Router::class, function () {
            return new Router($this->container);
        });
        $this->container->singleton(EventDispatcher::class, function () {
            return $this->events = new EventDispatcher();
        });
        $this->container->singleton(CacheManager::class, function () {
            $config = Config::get('cache', []);
            return $this->cache = new CacheManager($config);
        });
        $this->container->singleton(Logger::class, function () {
            $config = Config::get('logging', []);
            return $this->logger = new Logger($config);
        });
        $this->container->singleton(SessionManager::class, function () {
            $config = Config::get('session', []);
            return $this->session = new SessionManager($config);
        });
        $this->container->singleton(StorageManager::class, function () {
            $config = Config::get('storage', []);
            return $this->storage = new StorageManager($config);
        });
        $this->container->singleton(MailManager::class, function () {
            $config = Config::get('mail', []);
            return $this->mail = new MailManager($config);
        });

        // Initialize services immediately
        $this->events = $this->container->make(EventDispatcher::class);
        $this->cache = $this->container->make(CacheManager::class);
        $this->logger = $this->container->make(Logger::class);
        $this->session = $this->container->make(SessionManager::class);
        $this->storage = $this->container->make(StorageManager::class);
        $this->mail = $this->container->make(MailManager::class);

        // Initialize services
        $this->initializeServices();
    }

    /**
     * Initialize framework services
     */
    protected function initializeServices(): void
    {
        // Initialize database
        $dbConfig = Config::get('database');
        if ($dbConfig) {
            DatabaseManager::initialize($dbConfig);
        }

        // Initialize Blade templating
        $viewPath = $this->basePath . '/views';
        $cachePath = $this->basePath . '/storage/cache';
        \IsekaiPHP\Core\View::initialize($viewPath, $cachePath);

        // Initialize router
        $this->router = $this->container->make(Router::class);

        // Initialize and load modules
        $this->moduleManager = new ModuleManager($this->container, $this->basePath);
        $this->moduleManager->discover();
        $this->moduleManager->loadModules();
        $this->moduleManager->loadModuleConfigs();
        $this->moduleManager->registerViews();
        $this->moduleManager->registerRoutes($this->router);
        $this->moduleManager->registerMiddleware($this->router);
        // Module extensions are registered during loadModules() via registerModuleExtensions()

        // Register routes
        if (file_exists($this->basePath . '/routes/web.php')) {
            require $this->basePath . '/routes/web.php';
        }

        if (file_exists($this->basePath . '/routes/api.php')) {
            require $this->basePath . '/routes/api.php';
        }
    }

    /**
     * Handle an incoming HTTP request
     */
    public function handle(?Request $request = null): Response
    {
        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        try {
            $response = $this->router->dispatch($request);
        } catch (\Exception $e) {
            $response = $this->handleException($e, $request);
        }

        return $response;
    }

    /**
     * Handle exceptions
     */
    protected function handleException(\Exception $e, Request $request): Response
    {
        if (Config::get('app.debug')) {
            throw $e;
        }

        return new Response('Internal Server Error', 500);
    }

    /**
     * Get the base path
     */
    public function getBasePath(): string
    {
        return $this->basePath;
    }

    /**
     * Get the container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Get the router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Get the module manager
     */
    public function getModuleManager(): ?ModuleManager
    {
        return $this->moduleManager;
    }

    /**
     * Get the event dispatcher
     */
    public function getEventDispatcher(): ?EventDispatcher
    {
        return $this->events;
    }

    /**
     * Get the cache manager
     */
    public function getCacheManager(): ?CacheManager
    {
        return $this->cache;
    }

    /**
     * Get the logger
     */
    public function getLogger(): ?Logger
    {
        return $this->logger;
    }

    /**
     * Get the session manager
     */
    public function getSessionManager(): ?SessionManager
    {
        return $this->session;
    }

    /**
     * Get the storage manager
     */
    public function getStorageManager(): ?StorageManager
    {
        return $this->storage;
    }

    /**
     * Get the mail manager
     */
    public function getMailManager(): ?MailManager
    {
        return $this->mail;
    }

    /**
     * Run the application
     */
    public function run(): void
    {
        $request = Request::createFromGlobals();
        $response = $this->handle($request);
        $response->send();
    }
}

