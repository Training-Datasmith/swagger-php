<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Loggers;

use Psr\Log\Abstract_Logger;
use Psr\Log\Logger_Interface;
use Psr\Log\Log_Level;
class Console_Logger extends Abstract_Logger implements Logger_Interface
{
    public const COLOR_ERROR = "\x1b[31m";
    public const COLOR_WARNING = "\x1b[33m";
    public const COLOR_STOP = "\x1b[0m";
    private const LOG_LEVELS_UP_TO_NOTICE = [Log_Level::DEBUG, Log_Level::INFO, Log_Level::NOTICE];
    protected bool $logged_message_above_notice = false;
    public function __construct(protected bool $debug = false)
    {
    }
    public function logged_message_above_notice(): bool
    {
        return $this->logged_message_above_notice;
    }
    /**
     * @param string            $level
     * @param string|\Exception $message
     * @param array             $context additional details; supports custom <code>prefix</code> and <code>exception</code>
     */
    public function log($level, $message, array $context = []): void
    {
        $prefix = '';
        $color = '';
        // level adjustments
        switch ($level) {
            case Log_Level::DEBUG:
                if (!$this->debug) {
                    return;
                }
                $prefix = 'Debug: ';
            // no break
            case Log_Level::WARNING:
                $prefix = $prefix ?: $context['prefix'] ?? 'Warning: ';
                $color = static::COLOR_WARNING;
                break;
            case Log_Level::ERROR:
                $prefix = $context['prefix'] ?? 'Error: ';
                $color = static::COLOR_ERROR;
                break;
        }
        $stop = empty($color) ? '' : static::COLOR_STOP;
        if (!in_array($level, self::LOG_LEVELS_UP_TO_NOTICE, strict: true)) {
            $this->logged_message_above_notice = true;
        }
        /** @var ?\Exception $exception */
        $exception = $context['exception'] ?? null;
        if ($message instanceof \Exception) {
            $exception = $message;
            $message = $exception->get_message();
        }
        $log_line = sprintf('%s%s%s%s', $color, $prefix, $message, $stop);
        error_log($log_line);
        if ($this->debug) {
            if ($exception) {
                error_log($exception->get_trace_as_string());
            } elseif ($log_line !== '' && $log_line !== '0') {
                $stack = explode(PHP_EOL, (new \Exception())->get_trace_as_string());
                // self
                array_shift($stack);
                // AbstractLogger
                array_shift($stack);
                foreach ($stack as $line) {
                    error_log($line);
                }
            }
        }
    }
}