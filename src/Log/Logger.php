<?php

namespace IsekaiPHP\Log;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * Logger
 * 
 * PSR-3 compatible logger with multiple channels.
 */
class Logger implements LoggerInterface
{
    use LoggerTrait;

    protected array $channels = [];
    protected string $defaultChannel;
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->defaultChannel = $config['default'] ?? 'file';
        $this->initializeChannels();
    }

    /**
     * Initialize log channels
     */
    protected function initializeChannels(): void
    {
        $channels = $this->config['channels'] ?? [];

        foreach ($channels as $name => $channelConfig) {
            $driver = $channelConfig['driver'] ?? 'file';
            
            // Resolve relative paths to absolute paths
            if (isset($channelConfig['path']) && function_exists('base_path')) {
                $path = $channelConfig['path'];
                // If path is relative (starts with ../), resolve it
                if (strpos($path, '../') === 0 || strpos($path, './') === 0) {
                    $channelConfig['path'] = realpath($path) ?: $path;
                } elseif (!file_exists($path) && function_exists('base_path')) {
                    // Try to resolve relative to base path
                    $resolved = base_path($path);
                    if (file_exists(dirname($resolved)) || is_dir(dirname($resolved))) {
                        $channelConfig['path'] = $resolved;
                    }
                }
            }
            
            $this->channels[$name] = $this->createChannel($driver, $channelConfig);
        }
    }

    /**
     * Create a log channel
     */
    protected function createChannel(string $driver, array $config)
    {
        return match ($driver) {
            'file' => new Channels\FileChannel($config),
            'daily' => new Channels\DailyChannel($config),
            'syslog' => new Channels\SyslogChannel($config),
            default => new Channels\FileChannel($config),
        };
    }

    /**
     * Get a channel instance
     */
    public function channel(string $channel): LoggerInterface
    {
        if (!isset($this->channels[$channel])) {
            $this->channels[$channel] = $this->createChannel('file', []);
        }

        return $this->channels[$channel];
    }

    /**
     * Log a message
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $channel = $this->channels[$this->defaultChannel] ?? $this->channels['file'] ?? null;
        
        if ($channel) {
            $channel->log($level, $message, $context);
        }
    }
}

