<?php

namespace IsekaiPHP\Http;

use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Response extends SymfonyResponse
{
    /**
     * Create a JSON response
     */
    public static function json(array $data, int $status = 200, array $headers = []): self
    {
        $response = new static(json_encode($data), $status, array_merge([
            'Content-Type' => 'application/json',
        ], $headers));

        return $response;
    }

    /**
     * Create a redirect response
     */
    public static function redirect(string $url, int $status = 302): self
    {
        return new static('', $status, ['Location' => $url]);
    }

    /**
     * Send the response
     */
    public function send(): static
    {
        // Set default content type if not set
        if (!$this->headers->has('Content-Type')) {
            $this->headers->set('Content-Type', 'text/html; charset=UTF-8');
        }

        $this->sendHeaders();
        $this->sendContent();
        
        return $this;
    }

    /**
     * Set response content
     */
    public function setContent($content): static
    {
        if ($content instanceof \JsonSerializable || is_array($content)) {
            $content = json_encode($content);
            $this->headers->set('Content-Type', 'application/json');
        }

        return parent::setContent($content);
    }
}
