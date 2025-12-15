<?php

namespace IsekaiPHP\Http;

class Route
{
    protected array $methods;
    protected string $uri;
    protected $action;
    protected array $middleware;

    public function __construct(array $methods, string $uri, $action, array $middleware = [])
    {
        $this->methods = $methods;
        $this->uri = $uri;
        $this->action = $action;
        $this->middleware = $middleware;
    }

    /**
     * Check if route matches URI
     */
    public function matches(string $uri, ?array &$params = null): bool
    {
        $pattern = preg_replace('/\{([^}]+)\}/', '([^/]+)', $this->uri);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $uri, $matches)) {
            array_shift($matches);

            // Extract parameter names
            preg_match_all('/\{([^}]+)\}/', $this->uri, $paramNames);
            $paramNames = $paramNames[1];

            $params = [];
            foreach ($paramNames as $index => $name) {
                if (isset($matches[$index])) {
                    $params[$name] = $matches[$index];
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Get URI
     */
    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * Get action
     */
    public function getAction()
    {
        return $this->action;
    }

    /**
     * Get methods
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * Get middleware
     */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    /**
     * Add middleware
     */
    public function middleware($middleware): self
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, $middleware);
        } else {
            $this->middleware[] = $middleware;
        }
        return $this;
    }
}
