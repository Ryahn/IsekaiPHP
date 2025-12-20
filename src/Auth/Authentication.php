<?php

namespace IsekaiPHP\Auth;

use IsekaiPHP\Http\Request;
use IsekaiPHP\Models\User;

class Authentication
{
    protected const SESSION_KEY = 'user_id';

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
     * Attempt to login user
     */
    public function attempt(string $username, string $password, bool $remember = false): bool
    {
        $this->startSession();

        $user = User::where('username', $username)->orWhere('email', $username)->first();

        if (! $user || ! password_verify($password, $user->password)) {
            return false;
        }

        $this->login($user, $remember);

        return true;
    }

    /**
     * Login user
     */
    public function login(User $user, bool $remember = false): void
    {
        $this->startSession();

        $_SESSION[self::SESSION_KEY] = $user->id;

        // Regenerate session ID for security
        session_regenerate_id(true);

        if ($remember) {
            // Set remember me cookie (30 days)
            $token = bin2hex(random_bytes(32));
            // TODO: Store token in database and validate
            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
        }
    }

    /**
     * Logout user
     */
    public function logout(): void
    {
        $this->startSession();

        unset($_SESSION[self::SESSION_KEY]);

        // Destroy remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/', '', true, true);
        }

        session_destroy();
    }

    /**
     * Check if user is authenticated
     */
    public function check(): bool
    {
        $this->startSession();

        return isset($_SESSION[self::SESSION_KEY]);
    }

    /**
     * Get authenticated user
     */
    public function user(): ?User
    {
        if (! $this->check()) {
            return null;
        }

        return User::find($_SESSION[self::SESSION_KEY]);
    }

    /**
     * Get user ID
     */
    public function id(): ?int
    {
        $this->startSession();

        return $_SESSION[self::SESSION_KEY] ?? null;
    }

    /**
     * Get user from request
     */
    public function userFromRequest(Request $request): ?User
    {
        // Try to get from session
        $user = $this->user();
        if ($user) {
            return $user;
        }

        // Try to get from remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            // TODO: Validate remember token from database
            // For now, return null
            return null;
        }

        return null;
    }
}
