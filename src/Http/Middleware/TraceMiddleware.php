<?php

namespace Kardal\Trace\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Kardal\Trace\Support\TraceContext;
use Kardal\Trace\Support\TraceIdGenerator;

class TraceMiddleware
{
    /**
     * @param mixed $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $traceId = $this->resolveInboundTraceId($request);

        if (!$traceId) {
            $traceId = TraceIdGenerator::make();
        }

        TraceContext::set($traceId, 'request');

        $attributeKey = config('trace.request_attribute_key', 'correlation_id');
        if (is_object($request) && isset($request->attributes) && method_exists($request->attributes, 'set')) {
            $request->attributes->set($attributeKey, $traceId);
        }

        config(['app.uuid' => $traceId]);
        app()->instance($attributeKey, $traceId);
        app()->instance('trace.correlation_id', $traceId);

        $this->setLogContext($traceId);

        $response = $next($request);

        $responseHeader = config('trace.response_header_name', config('trace.header_name', 'X-Correlation-Id'));
        if (is_object($response) && isset($response->headers) && method_exists($response->headers, 'set')) {
            $response->headers->set($responseHeader, $traceId);
        }

        return $response;
    }

    /**
     * @param mixed $request
     * @return string|null
     */
    protected function resolveInboundTraceId($request)
    {
        $candidates = (array) config('trace.inbound_header_candidates', [
            'X-Correlation-Id',
            'X-Request-Id',
            'X-Trace-Id',
        ]);

        foreach ($candidates as $headerName) {
            $headerName = trim((string) $headerName);
            if ($headerName === '') {
                continue;
            }

            $value = null;
            if (is_object($request) && method_exists($request, 'header')) {
                $value = $request->header($headerName);
            }

            if (is_array($value)) {
                $value = reset($value);
            }

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    /**
     * @param string $traceId
     * @return void
     */
    protected function setLogContext($traceId)
    {
        $contextKey = config('trace.log_context_key', 'correlation_id');
        $root = Log::getFacadeRoot();

        if ($root && method_exists($root, 'withContext')) {
            Log::withContext([$contextKey => $traceId]);
        }
    }
}
