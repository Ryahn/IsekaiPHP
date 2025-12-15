<?php

namespace IsekaiPHP\Log\Channels;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Daily Log Channel
 * 
 * Creates a new log file each day.
 */
class DailyChannel implements LoggerInterface
{
    use LoggerTrait;

    protected string $path;
    protected int $days;
    protected int $permission;

    public function __construct(array $config = [])
    {
        $basePath = $config['path'] ?? sys_get_temp_dir();
        $this->path = rtrim($basePath, '/') . '/logs';
        $this->days = $config['days'] ?? 7;
        $this->permission = $config['permission'] ?? 0644;

        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    /**
     * Get log file path for today
     */
    protected function getLogFile(): string
    {
        $date = date('Y-m-d');
        return $this->path . '/isekaiphp-' . $date . '.log';
    }

    /**
     * Log a message
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $file = $this->getLogFile();
        $timestamp = date('Y-m-d H:i:s');
        $level = strtoupper($level);
        $message = $this->formatMessage($message, $context);
        $logEntry = "[{$timestamp}] {$level}: {$message}" . PHP_EOL;

        file_put_contents($file, $logEntry, FILE_APPEND | LOCK_EX);

        // Clean up old log files
        $this->cleanOldLogs();
    }

    /**
     * Format log message with context
     */
    protected function formatMessage(string|\Stringable $message, array $context): string
    {
        $message = (string)$message;

        if (empty($context)) {
            return $message;
        }

        $replace = [];
        foreach ($context as $key => $value) {
            $replace['{' . $key . '}'] = is_scalar($value) ? $value : json_encode($value);
        }

        return strtr($message, $replace);
    }

    /**
     * Clean up old log files
     */
    protected function cleanOldLogs(): void
    {
        $files = glob($this->path . '/isekaiphp-*.log');
        $cutoff = time() - ($this->days * 24 * 60 * 60);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
            }
        }
    }
}

