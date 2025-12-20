<?php

namespace IsekaiPHP\Http\Middleware;

use IsekaiPHP\Http\Request;
use IsekaiPHP\Http\Response;

class CSRFMiddleware
{
    protected const TOKEN_KEY = '_token';

    /**
     * Start session if not started
     */
    protected function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Handle the request
     */
    public function handle(Request $request, \Closure $next): Response
    {
        $this->startSession();

        // Skip CSRF for GET, HEAD, OPTIONS requests
        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        // Get token from request
        $token = $request->input(self::TOKEN_KEY) ?: $request->headers->get('X-CSRF-TOKEN');

        // Validate token
        if (! $token || ! $this->validateToken($token)) {
            if ($request->expectsJson()) {
                return Response::json(['error' => 'CSRF token mismatch'], 419);
            }

            return new Response('CSRF token mismatch', 419);
        }

        return $next($request);
    }

    /**
     * Validate CSRF token
     */
    protected function validateToken(string $token): bool
    {
        $sessionToken = $_SESSION['_csrf_token'] ?? null;

        return hash_equals($sessionToken, $token);
    }

    /**
     * Generate CSRF token
     */
    public static function generateToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (! isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }

    /**
     * Get CSRF token
     */
    public static function getToken(): string
    {
        return self::generateToken();
    }
}
