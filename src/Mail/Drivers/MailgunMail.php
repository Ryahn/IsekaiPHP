<?php

namespace IsekaiPHP\Mail\Drivers;

use IsekaiPHP\Mail\MailInterface;

/**
 * Mailgun Mail Driver
 *
 * Requires Mailgun PHP SDK.
 */
class MailgunMail implements MailInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Send an email
     */
    public function send(string $to, string $subject, string $message, array $options = []): bool
    {
        // Placeholder - would use Mailgun SDK
        // This requires: composer require mailgun/mailgun-php
        return false;
    }
}
