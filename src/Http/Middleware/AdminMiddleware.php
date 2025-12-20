<?php

namespace IsekaiPHP\Http\Middleware;

use IsekaiPHP\Auth\Authentication;
use IsekaiPHP\Core\Container;
use IsekaiPHP\Http\Request;
use IsekaiPHP\Http\Response;

class AdminMiddleware
{
    protected Authentication $auth;
    protected Container $container;

    public function __construct(Authentication $auth, Container $container)
    {
        $this->auth = $auth;
        $this->container = $container;
    }

    /**
     * Handle the request
     */
    public function handle(Request $request, \Closure $next): Response
    {
        // Check if user is authenticated
        if (! $this->auth->check()) {
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Unauthorized'], 401);
            }

            return Response::redirect('/login');
        }

        // Check if permission system exists and hook into it
        // This allows roles/permissions module to extend this behavior
        if ($this->container->has('admin.permission.checker')) {
            $checker = $this->container->make('admin.permission.checker');
            if (is_callable($checker)) {
                $hasPermission = call_user_func($checker, $this->auth->user());
                if (! $hasPermission) {
                    if ($request->expectsJson()) {
                        return Response::json(['error' => 'Forbidden'], 403);
                    }

                    return new Response('Forbidden', 403);
                }
            }
        }
        // If no permission system exists, allow any authenticated user
        // Roles/permissions module can be installed later to add restrictions

        return $next($request);
    }
}
