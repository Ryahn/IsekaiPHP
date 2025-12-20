<?php

namespace IsekaiPHP\Mail\Drivers;

use IsekaiPHP\Mail\MailInterface;

/**
 * SMTP Mail Driver
 */
class SmtpMail implements MailInterface
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
        $from = $options['from'] ?? $this->config['from'] ?? 'noreply@example.com';
        $fromName = $options['from_name'] ?? $this->config['from_name'] ?? '';
        $headers = $this->buildHeaders($from, $fromName, $options);

        return mail($to, $subject, $message, $headers);
    }

    /**
     * Build email headers
     */
    protected function buildHeaders(string $from, string $fromName, array $options): string
    {
        $headers = [];
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';

        if ($fromName) {
            $headers[] = "From: {$fromName} <{$from}>";
        } else {
            $headers[] = "From: {$from}";
        }

        if (isset($options['reply_to'])) {
            $headers[] = "Reply-To: {$options['reply_to']}";
        }

        if (isset($options['cc'])) {
            $headers[] = "Cc: {$options['cc']}";
        }

        if (isset($options['bcc'])) {
            $headers[] = "Bcc: {$options['bcc']}";
        }

        return implode("\r\n", $headers);
    }
}
