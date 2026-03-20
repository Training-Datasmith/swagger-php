<?php

declare (strict_types=1);
/**
 * @license Apache 2.0
 */
namespace Open_Api\Loggers;

use Psr\Log\Abstract_Logger;
use Psr\Log\Logger_Interface;
use Psr\Log\Log_Level;
class Default_Logger extends Abstract_Logger implements Logger_Interface
{
    public function log($level, $message, array $context = []): void
    {
        if (Log_Level::DEBUG == $level) {
            return;
        }
        $error_level = in_array($level, [Log_Level::NOTICE, Log_Level::INFO]) ? E_USER_NOTICE : E_USER_WARNING;
        trigger_error($message, $error_level);
    }
}