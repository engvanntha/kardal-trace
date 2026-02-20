<?php

namespace Kardal\Trace\Logging;

class CustomizeMonolog
{
    /**
     * @param mixed $logger
     * @return void
     */
    public function __invoke($logger)
    {
        if (!is_object($logger) || !method_exists($logger, 'pushProcessor')) {
            return;
        }

        $logger->pushProcessor(new TraceLogProcessor());
    }
}
