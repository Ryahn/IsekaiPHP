<?php

namespace MailModule\Mail\Drivers;

use IsekaiPHP\Mail\MailInterface;

/**
 * Postmark Mail Driver
 * 
 * Example custom mail driver for a module.
 */
class PostmarkMail implements MailInterface
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Send an email via Postmark API
     */
    public function send(string $to, string $subject, string $message, array $options = []): bool
    {
        $apiKey = $this->config['api_key'] ?? '';
        $serverToken = $this->config['server_token'] ?? '';
        
        if (empty($serverToken)) {
            return false;
        }

        $from = $options['from'] ?? $this->config['from'] ?? 'noreply@example.com';
        $fromName = $options['from_name'] ?? $this->config['from_name'] ?? '';

        // Postmark API call would go here
        // This is a placeholder implementation
        $data = [
            'From' => $fromName ? "{$fromName} <{$from}>" : $from,
            'To' => $to,
            'Subject' => $subject,
            'HtmlBody' => $message,
            'MessageStream' => $this->config['message_stream'] ?? 'outbound',
        ];

        // In a real implementation, you would make an HTTP request to Postmark API
        // $response = $this->makeApiRequest('https://api.postmarkapp.com/email', $data, $serverToken);
        
        return true; // Placeholder
    }

    /**
     * Make API request to Postmark
     */
    protected function makeApiRequest(string $url, array $data, string $token): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Content-Type: application/json',
            'X-Postmark-Server-Token: ' . $token,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'success' => $httpCode === 200,
            'response' => json_decode($response, true),
        ];
    }
}

