<?php

namespace Kardal\Trace\Http;

use Kardal\Trace\Support\TraceContext;
use Kardal\Trace\Support\TraceIdGenerator;

class GuzzleTraceMiddleware
{
    /**
     * @param string|null $headerName
     * @param string|null $traceId
     * @return callable
     */
    public static function factory($headerName = null, $traceId = null)
    {
        return function (callable $handler) use ($headerName, $traceId) {
            return function ($request, array $options = []) use ($handler, $headerName, $traceId) {
                $traceId = $traceId ?: TraceContext::get();
                if (!$traceId) {
                    $traceId = TraceIdGenerator::make();
                    TraceContext::set($traceId, 'guzzle');
                }

                $resolvedHeader = self::resolveHeaderName($headerName);

                if (is_object($request) && method_exists($request, 'hasHeader') && !$request->hasHeader($resolvedHeader)) {
                    $request = $request->withHeader($resolvedHeader, $traceId);
                }

                return $handler($request, $options);
            };
        };
    }

    /**
     * @param string|null $headerName
     * @return string
     */
    protected static function resolveHeaderName($headerName = null)
    {
        $headerName = is_string($headerName) ? trim($headerName) : '';
        if ($headerName !== '') {
            return $headerName;
        }

        return config('trace.header_name', 'X-Correlation-Id');
    }
}
