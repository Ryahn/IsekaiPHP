<?php

namespace IsekaiPHP\Http\Middleware;

use IsekaiPHP\Auth\Authentication;
use IsekaiPHP\Http\Request;
use IsekaiPHP\Http\Response;

class AuthMiddleware
{
    protected Authentication $auth;

    public function __construct(Authentication $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle the request
     */
    public function handle(Request $request, \Closure $next): Response
    {
        if (! $this->auth->check()) {
            if ($request->expectsJson()) {
                return Response::json(['error' => 'Unauthorized'], 401);
            }

            return Response::redirect('/login');
        }

        return $next($request);
    }
}
