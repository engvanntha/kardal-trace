<?php

namespace Kardal\Trace\Logging;

use Kardal\Trace\Support\TraceContext;

class TraceLogProcessor
{
    /**
     * @param array $record
     * @return array
     */
    public function __invoke(array $record)
    {
        $traceId = TraceContext::get();
        if (!$traceId) {
            return $record;
        }

        $contextKey = config('trace.log_context_key', 'correlation_id');

        if (!isset($record['context']) || !is_array($record['context'])) {
            $record['context'] = [];
        }

        if (!array_key_exists($contextKey, $record['context'])) {
            $record['context'][$contextKey] = $traceId;
        }

        if (!isset($record['extra']) || !is_array($record['extra'])) {
            $record['extra'] = [];
        }

        if (!isset($record['extra']['trace_source'])) {
            $record['extra']['trace_source'] = TraceContext::source();
        }

        return $record;
    }
}
