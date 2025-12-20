<?php

namespace IsekaiPHP\Mail;

/**
 * Mail Interface
 */
interface MailInterface
{
    /**
     * Send an email
     */
    public function send(string $to, string $subject, string $message, array $options = []): bool;
}
