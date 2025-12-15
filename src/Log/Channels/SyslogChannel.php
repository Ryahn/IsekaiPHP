<?php

namespace IsekaiPHP\Log\Channels;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;

/**
 * Syslog Channel
 */
class SyslogChannel implements LoggerInterface
{
    use LoggerTrait;

    protected string $ident;
    protected int $facility;

    public function __construct(array $config = [])
    {
        $this->ident = $config['ident'] ?? 'isekaiphp';
        $this->facility = $config['facility'] ?? LOG_USER;

        openlog($this->ident, LOG_PID | LOG_CONS, $this->facility);
    }

    /**
     * Log a message
     */
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $level = $this->getSyslogLevel($level);
        $message = $this->formatMessage($message, $context);

        syslog($level, $message);
    }

    /**
     * Get syslog level from PSR-3 level
     */
    protected function getSyslogLevel(string $level): int
    {
        return match (strtolower($level)) {
            'emergency' => LOG_EMERG,
            'alert' => LOG_ALERT,
            'critical' => LOG_CRIT,
            'error' => LOG_ERR,
            'warning' => LOG_WARNING,
            'notice' => LOG_NOTICE,
            'info' => LOG_INFO,
            'debug' => LOG_DEBUG,
            default => LOG_INFO,
        };
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
     * Close syslog connection
     */
    public function __destruct()
    {
        closelog();
    }
}

