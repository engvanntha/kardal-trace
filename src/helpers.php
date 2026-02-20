<?php

use Kardal\Trace\Http\GuzzleTraceMiddleware;
use Kardal\Trace\Support\CurlTrace;
use Kardal\Trace\Support\TraceContext;
use Kardal\Trace\Support\TraceIdGenerator;

if (!function_exists('trace_id')) {
    /**
     * @param string|null $default
     * @return string|null
     */
    function trace_id($default = null)
    {
        return TraceContext::get($default);
    }
}

if (!function_exists('trace_set_id')) {
    /**
     * @param string $traceId
     * @param string $source
     * @return string
     */
    function trace_set_id($traceId, $source = 'manual')
    {
        TraceContext::set($traceId, $source);

        return TraceContext::get();
    }
}

if (!function_exists('trace_headers')) {
    /**
     * @param string|null $traceId
     * @param array $extra
     * @param string|null $headerName
     * @return array
     */
    function trace_headers($traceId = null, array $extra = [], $headerName = null)
    {
        $traceId = $traceId ?: TraceContext::get();
        if (!$traceId) {
            $traceId = TraceIdGenerator::make();
            TraceContext::set($traceId, 'helper');
        }

        $headerName = is_string($headerName) && trim($headerName) !== ''
            ? trim($headerName)
            : config('trace.header_name', 'X-Correlation-Id');

        $headers = [$headerName => $traceId];

        return array_merge($headers, $extra);
    }
}

if (!function_exists('trace_guzzle_middleware')) {
    /**
     * @param string|null $headerName
     * @param string|null $traceId
     * @return callable
     */
    function trace_guzzle_middleware($headerName = null, $traceId = null)
    {
        return GuzzleTraceMiddleware::factory($headerName, $traceId);
    }
}

if (!function_exists('trace_curl_headers')) {
    /**
     * @param array $headerLines
     * @param string|null $traceId
     * @param string|null $headerName
     * @return array
     */
    function trace_curl_headers(array $headerLines = [], $traceId = null, $headerName = null)
    {
        return CurlTrace::appendHeaderLines($headerLines, $traceId, $headerName);
    }
}

if (!function_exists('trace_curl_apply')) {
    /**
     * @param resource $curlHandle
     * @param array $headerLines
     * @param string|null $traceId
     * @param string|null $headerName
     * @return array
     */
    function trace_curl_apply($curlHandle, array $headerLines = [], $traceId = null, $headerName = null)
    {
        return CurlTrace::apply($curlHandle, $headerLines, $traceId, $headerName);
    }
}
