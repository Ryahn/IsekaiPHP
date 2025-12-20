<?php

namespace IsekaiPHP\Core;

class Container
{
    protected array $bindings = [];
    protected array $singletons = [];
    protected array $instances = [];

    /**
     * Bind a class or interface to a resolver
     */
    public function bind(string $abstract, $concrete = null, bool $singleton = false): void
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton,
        ];
    }

    /**
     * Bind a singleton instance
     */
    public function singleton(string $abstract, $concrete = null): void
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance
     */
    public function instance(string $abstract, $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve a class from the container
     */
    public function make(string $abstract, array $parameters = [])
    {
        // Return existing instance if singleton
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get concrete class/resolver
        $concrete = $this->getConcrete($abstract);

        // If concrete is different from abstract, we have a binding
        // If it's a closure, build will handle it
        // If it's the same class, we need to auto-resolve

        // Resolve the concrete class
        $object = $this->build($concrete, $parameters);

        // Store as singleton if needed
        if (isset($this->bindings[$abstract]) && $this->bindings[$abstract]['singleton']) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Get the concrete class for an abstract
     */
    protected function getConcrete(string $abstract)
    {
        // Check for exact match first
        if (isset($this->bindings[$abstract])) {
            return $this->bindings[$abstract]['concrete'];
        }

        return $abstract;
    }

    /**
     * Check if a binding exists
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Build an instance of the given class
     */
    protected function build($concrete, array $parameters = [])
    {
        // If concrete is a closure, call it
        if ($concrete instanceof \Closure) {
            // Check if closure accepts parameters
            $reflection = new \ReflectionFunction($concrete);
            $closureParams = $reflection->getParameters();

            // If closure accepts parameters, pass container and/or provided parameters
            if (count($closureParams) > 0) {
                $closureParamName = $closureParams[0]->getName();
                // If first param is 'container' or type-hinted as Container, pass container
                if (
                    $closureParamName === 'container'
                    || ($closureParams[0]->hasType()
                        && $closureParams[0]->getType()->getName() === Container::class)
                ) {
                    return $concrete($this, ...$parameters);
                }

                // Otherwise, pass provided parameters
                return $concrete(...$parameters);
            }

            // If no parameters needed, call without args
            return $concrete();
        }

        // If we got here, concrete is a class name string
        // Use reflection to instantiate
        if (! class_exists($concrete)) {
            throw new \Exception("Class {$concrete} does not exist");
        }

        $reflector = new \ReflectionClass($concrete);

        if (! $reflector->isInstantiable()) {
            throw new \Exception("Class {$concrete} is not instantiable");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            return new $concrete();
        }

        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies, $parameters);

        return $reflector->newInstanceArgs($instances);
    }

    /**
     * Resolve dependencies for a method
     */
    protected function resolveDependencies(array $dependencies, array $parameters): array
    {
        $results = [];
        $paramIndex = 0;

        foreach ($dependencies as $dependency) {
            // If parameter is provided, use it
            if (isset($parameters[$paramIndex])) {
                $results[] = $parameters[$paramIndex];
                $paramIndex++;

                continue;
            }

            // Check for named parameter
            if (isset($parameters[$dependency->getName()])) {
                $results[] = $parameters[$dependency->getName()];

                continue;
            }

            // Try to resolve from container
            $class = $dependency->getType();
            if ($class && ! $class->isBuiltin()) {
                $results[] = $this->make($class->getName());

                continue;
            }

            // Use default value if available
            if ($dependency->isDefaultValueAvailable()) {
                $results[] = $dependency->getDefaultValue();

                continue;
            }

            throw new \Exception("Unable to resolve dependency: {$dependency->getName()}");
        }

        return $results;
    }

    /**
     * Check if a binding exists
     */
    public function bound(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }
}
