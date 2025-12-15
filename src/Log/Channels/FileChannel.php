<?php

namespace IsekaiPHP\Log\Channels;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;

/**
 * File Log Channel
 */
class FileChannel implements LoggerInterface
{
    use LoggerTrait;

    protected string $path;
    protected int $permission;

    public function __construct(array $config = [])
    {
        $defaultPath = sys_get_temp_dir() . '/isekaiphp.log';
        
        // Resolve storage_path() if it's a function call string
        if (isset($config['path'])) {
            $path = $config['path'];
            // If path contains storage_path(), evaluate it
            if (strpos($path, 'storage_path(') !== false && function_exists('storage_path')) {
                // Extract the path argument
                if (preg_match("/storage_path\('([^']+)'\)/", $path, $matches)) {
                    $this->path = storage_path($matches[1]);
                } else {
                    $this->path = $path;
                }
            } else {
                $this->path = $path;
            }
        } else {
            $this->path = $defaultPath;
        }
        
        $this->permission = $config['permission'] ?? 0644;

        // Ensure directory exists
        $dir = dirname($this->path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Log a message
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $level = strtoupper($level);
        $message = $this->formatMessage($message, $context);
        $logEntry = "[{$timestamp}] {$level}: {$message}" . PHP_EOL;

        file_put_contents($this->path, $logEntry, FILE_APPEND | LOCK_EX);
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
}

