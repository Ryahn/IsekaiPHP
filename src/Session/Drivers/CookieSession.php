<?php

namespace IsekaiPHP\Session\Drivers;

use IsekaiPHP\Session\SessionInterface;

/**
 * Cookie Session Driver
 *
 * Stores session data in encrypted cookies.
 */
class CookieSession implements SessionInterface
{
    protected string $name;
    protected int $lifetime;
    protected string $path;
    protected string $domain;
    protected bool $secure;
    protected bool $httpOnly;
    protected string $encryptionKey;

    public function __construct(array $config = [])
    {
        $this->name = $config['name'] ?? 'isekaiphp_session';
        $this->lifetime = $config['lifetime'] ?? 7200;
        $this->path = $config['path'] ?? '/';
        $this->domain = $config['domain'] ?? '';
        $this->secure = $config['secure'] ?? false;
        $this->httpOnly = $config['http_only'] ?? true;
        $this->encryptionKey = $config['encryption_key'] ?? 'default-key-change-in-production';
    }

    /**
     * Start the session
     */
    public function start(): void
    {
        // Load session from cookie
        if (isset($_COOKIE[$this->name])) {
            $data = $this->decrypt($_COOKIE[$this->name]);
            if ($data) {
                $_SESSION = json_decode($data, true) ?: [];
            }
        } else {
            $_SESSION = [];
        }
    }

    /**
     * Save session to cookie
     */
    public function save(): void
    {
        $data = json_encode($_SESSION);
        $encrypted = $this->encrypt($data);

        setcookie(
            $this->name,
            $encrypted,
            time() + $this->lifetime,
            $this->path,
            $this->domain,
            $this->secure,
            $this->httpOnly
        );
    }

    /**
     * Encrypt data
     */
    protected function encrypt(string $data): string
    {
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $this->encryptionKey, 0, $iv);

        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt data
     */
    protected function decrypt(string $data): ?string
    {
        $data = base64_decode($data, true);
        if ($data === false) {
            return null;
        }

        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);

        return openssl_decrypt($encrypted, 'AES-256-CBC', $this->encryptionKey, 0, $iv);
    }
}
