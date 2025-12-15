<?php

namespace IsekaiPHP\Core;

use IsekaiPHP\Database\DatabaseManager;
use IsekaiPHP\Http\Request;
use IsekaiPHP\Http\Response;
use IsekaiPHP\Http\Router;

class Application
{
    protected Container $container;
    protected string $basePath;
    protected Router $router;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->container = new Container();

        // Set global app instance early so routes can access it
        $GLOBALS['app'] = $this;

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

        // Load configuration
        Config::load($this->basePath);

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
     * Run the application
     */
    public function run(): void
    {
        $request = Request::createFromGlobals();
        $response = $this->handle($request);
        $response->send();
    }
}

