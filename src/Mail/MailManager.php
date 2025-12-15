<?php

namespace IsekaiPHP\Mail;

use IsekaiPHP\Core\Config;

/**
 * Mail Manager
 * 
 * Manages email sending with multiple drivers.
 */
class MailManager
{
    protected array $config;
    protected string $defaultDriver;
    protected array $drivers = [];
    protected array $customDrivers = [];

    public function __construct(array $config = [])
    {
        $this->config = $config ?: Config::get('mail', []);
        $this->defaultDriver = $this->config['default'] ?? 'smtp';
    }

    /**
     * Register a custom mail driver
     */
    public function extend(string $driver, callable $callback): void
    {
        $this->customDrivers[$driver] = $callback;
    }

    /**
     * Get a mail driver instance
     */
    public function driver(?string $driver = null): MailInterface
    {
        $driver = $driver ?? $this->defaultDriver;

        if (!isset($this->drivers[$driver])) {
            $this->drivers[$driver] = $this->createDriver($driver);
        }

        return $this->drivers[$driver];
    }

    /**
     * Create a mail driver instance
     */
    protected function createDriver(string $driver): MailInterface
    {
        $config = $this->config['drivers'][$driver] ?? [];

        // Check for custom driver first (registered by modules)
        if (isset($this->customDrivers[$driver])) {
            $instance = call_user_func($this->customDrivers[$driver], $config);
            if ($instance instanceof MailInterface) {
                return $instance;
            }
        }

        // Use built-in drivers
        return match ($driver) {
            'smtp' => new Drivers\SmtpMail($config),
            'mailgun' => new Drivers\MailgunMail($config),
            'sendgrid' => new Drivers\SendgridMail($config),
            default => new Drivers\SmtpMail($config),
        };
    }

    /**
     * Send an email
     */
    public function send(string $to, string $subject, string $message, array $options = []): bool
    {
        return $this->driver()->send($to, $subject, $message, $options);
    }

    /**
     * Send email using a view
     */
    public function sendView(string $to, string $subject, string $view, array $data = [], array $options = []): bool
    {
        $message = \IsekaiPHP\Core\View::render($view, $data);
        return $this->send($to, $subject, $message, $options);
    }
}

