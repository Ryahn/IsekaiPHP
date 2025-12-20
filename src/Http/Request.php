<?php

namespace IsekaiPHP\Http;

use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class Request extends SymfonyRequest
{
    /**
     * Create a request from PHP globals
     */
    public static function createFromGlobals(): static
    {
        $request = parent::createFromGlobals();

        return new static(
            $request->query->all(),
            $request->request->all(),
            $request->attributes->all(),
            $request->cookies->all(),
            $request->files->all(),
            $request->server->all(),
            $request->getContent()
        );
    }

    /**
     * Get input value
     */
    public function input(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->request->all() + $this->query->all();
        }

        return $this->request->get($key, $this->query->get($key, $default));
    }

    /**
     * Get all input
     */
    public function all(): array
    {
        return $this->request->all() + $this->query->all();
    }

    /**
     * Get query parameter
     */
    public function query(?string $key = null, $default = null)
    {
        if ($key === null) {
            return $this->query->all();
        }

        return $this->query->get($key, $default);
    }

    /**
     * Check if request has key
     */
    public function has(string $key): bool
    {
        return $this->request->has($key) || $this->query->has($key);
    }

    /**
     * Get file from request
     */
    public function file(string $key)
    {
        $file = $this->files->get($key);

        // If Symfony UploadedFile, return as-is
        if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            return $file;
        }

        // Otherwise return array with file info for manual handling
        return $file;
    }

    /**
     * Check if request has file
     */
    public function hasFile(string $key): bool
    {
        if (! $this->files->has($key)) {
            return false;
        }

        $file = $this->files->get($key);

        if ($file instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
            return $file->isValid();
        }

        // Check PHP $_FILES array
        return isset($_FILES[$key]) && $_FILES[$key]['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get the request method
     */
    public function method(): string
    {
        return $this->getMethod();
    }

    /**
     * Check if request is POST
     */
    public function isPost(): bool
    {
        return $this->isMethod('POST');
    }

    /**
     * Check if request is GET
     */
    public function isGet(): bool
    {
        return $this->isMethod('GET');
    }

    /**
     * Get the request path
     */
    public function path(): string
    {
        return $this->getPathInfo();
    }

    /**
     * Get the full URL
     */
    public function url(): string
    {
        return $this->getUri();
    }

    /**
     * Get the request IP address
     */
    public function ip(): string
    {
        return $this->getClientIp();
    }

    /**
     * Check if request expects JSON
     */
    public function expectsJson(): bool
    {
        return $this->getAcceptableContentTypes()[0] ?? null === 'application/json' ||
               $this->isXmlHttpRequest();
    }
}
