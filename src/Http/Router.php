<?php

namespace IsekaiPHP\Http;

use IsekaiPHP\Core\Container;

class Router
{
    protected Container $container;
    protected array $routes = [];
    protected array $middleware = [];
    protected array $globalMiddleware = [];
    protected array $groupMiddleware = [];
    protected string $prefix = '';

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register a GET route
     */
    public function get(string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute(['GET'], $uri, $action, $middleware);
    }

    /**
     * Register a POST route
     */
    public function post(string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute(['POST'], $uri, $action, $middleware);
    }

    /**
     * Register a PUT route
     */
    public function put(string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute(['PUT'], $uri, $action, $middleware);
    }

    /**
     * Register a DELETE route
     */
    public function delete(string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute(['DELETE'], $uri, $action, $middleware);
    }

    /**
     * Register a route with multiple methods
     */
    public function match(array $methods, string $uri, $action, array $middleware = []): Route
    {
        return $this->addRoute($methods, $uri, $action, $middleware);
    }

    /**
     * Add a route
     */
    protected function addRoute(array $methods, string $uri, $action, array $middleware = []): Route
    {
        $uri = $this->prefix . $uri;
        $route = new Route($methods, $uri, $action, $middleware);

        foreach ($methods as $method) {
            $this->routes[$method][$uri] = $route;
        }

        return $route;
    }

    /**
     * Create a route group
     */
    public function group(array $attributes, \Closure $callback): void
    {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->groupMiddleware;

        if (isset($attributes['prefix'])) {
            $this->prefix = $previousPrefix . $attributes['prefix'];
        }

        if (isset($attributes['middleware'])) {
            $this->groupMiddleware = array_merge($previousMiddleware, (array)$attributes['middleware']);
        }

        $callback($this);

        $this->prefix = $previousPrefix;
        $this->groupMiddleware = $previousMiddleware;
    }
    
    /**
     * Get router instance (for use in route files)
     */
    public static function getInstance(): ?self
    {
        return $GLOBALS['app']->getRouter() ?? null;
    }

    /**
     * Dispatch a request
     */
    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $uri = $request->getPathInfo();
        
        // Normalize URI (remove trailing slash except for root)
        $normalizedUri = $uri !== '/' ? rtrim($uri, '/') : $uri;

        // Try to find exact match first (with and without trailing slash)
        if (isset($this->routes[$method][$uri])) {
            $route = $this->routes[$method][$uri];
            return $this->runRoute($route, $request);
        }
        
        // Try normalized version
        if (isset($this->routes[$method][$normalizedUri])) {
            $route = $this->routes[$method][$normalizedUri];
            return $this->runRoute($route, $request);
        }
        
        // Try with trailing slash
        $uriWithSlash = $normalizedUri . '/';
        if (isset($this->routes[$method][$uriWithSlash])) {
            $route = $this->routes[$method][$uriWithSlash];
            return $this->runRoute($route, $request);
        }

        // Try to match with parameters
        foreach ($this->routes[$method] ?? [] as $route) {
            $params = [];
            if ($route->matches($uri, $params) || $route->matches($normalizedUri, $params)) {
                $request->attributes->add($params);
                return $this->runRoute($route, $request);
            }
        }

        // Return 404
        return new Response('Not Found', 404);
    }

    /**
     * Register global middleware
     */
    public function middleware(string $middleware): void
    {
        $this->globalMiddleware[] = $middleware;
    }

    /**
     * Run a route
     */
    protected function runRoute(Route $route, Request $request): Response
    {
        // Merge global, group, and route middleware
        $middleware = array_merge($this->globalMiddleware, $this->groupMiddleware, $route->getMiddleware());

        // If no middleware, run action directly
        if (empty($middleware)) {
            return $this->runAction($route, $request);
        }

        // Run middleware in reverse order (last middleware wraps first)
        $next = function ($req) use ($route) {
            return $this->runAction($route, $req);
        };

        foreach (array_reverse($middleware) as $middlewareClass) {
            // Handle middleware with parameters (e.g., MiddlewareClass::class . '@param')
            if (is_string($middlewareClass) && strpos($middlewareClass, '@') !== false) {
                [$middlewareClass, $param] = explode('@', $middlewareClass, 2);
                $middlewareInstance = $this->container->make($middlewareClass, ['param' => $param]);
            } else {
                $middlewareInstance = $this->container->make($middlewareClass);
            }

            $previousNext = $next;
            $next = function ($req) use ($middlewareInstance, $previousNext) {
                return $middlewareInstance->handle($req, $previousNext);
            };
        }

        return $next($request);
    }

    /**
     * Run route action
     */
    protected function runAction(Route $route, Request $request): Response
    {
        $action = $route->getAction();

        if ($action instanceof \Closure) {
            // Get route parameters from request attributes
            $params = $request->attributes->all();
            // Remove non-route params
            unset($params['_route'], $params['_controller']);
            // Pass request and parameters to closure
            return call_user_func_array($action, array_merge([$request], array_values($params)));
        }

        if (is_string($action) && strpos($action, '@') !== false) {
            [$controller, $method] = explode('@', $action);
            $controller = $this->container->make($controller);
            return $controller->$method($request);
        }

        if (is_string($action)) {
            $controller = $this->container->make($action);
            return $controller($request);
        }

        if (is_array($action)) {
            [$controller, $method] = $action;
            $controller = $this->container->make($controller);
            return $controller->$method($request);
        }

        throw new \Exception('Invalid route action');
    }
}
