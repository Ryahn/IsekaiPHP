<?php

namespace IsekaiPHP\Mail\Drivers;

use IsekaiPHP\Mail\MailInterface;

/**
 * SendGrid Mail Driver
 *
 * Requires SendGrid PHP SDK.
 */
class SendgridMail implements MailInterface
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
        // Placeholder - would use SendGrid SDK
        // This requires: composer require sendgrid/sendgrid
        return false;
    }
}
