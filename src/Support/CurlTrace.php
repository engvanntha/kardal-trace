<?php

namespace Kardal\Trace\Support;

class CurlTrace
{
    /**
     * @param array $headerLines
     * @param string|null $traceId
     * @param string|null $headerName
     * @return array
     */
    public static function appendHeaderLines(array $headerLines = [], $traceId = null, $headerName = null)
    {
        $traceId = $traceId ?: TraceContext::get();
        if (!$traceId) {
            $traceId = TraceIdGenerator::make();
            TraceContext::set($traceId, 'curl');
        }

        $headerName = self::resolveHeaderName($headerName);
        $prefix = strtolower($headerName) . ':';
        $exists = false;

        foreach ($headerLines as $line) {
            if (is_string($line) && strpos(strtolower($line), $prefix) === 0) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $headerLines[] = $headerName . ': ' . $traceId;
        }

        return $headerLines;
    }

    /**
     * @param resource $curlHandle
     * @param array $headerLines
     * @param string|null $traceId
     * @param string|null $headerName
     * @return array
     */
    public static function apply($curlHandle, array $headerLines = [], $traceId = null, $headerName = null)
    {
        $headerLines = self::appendHeaderLines($headerLines, $traceId, $headerName);
        curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headerLines);

        return $headerLines;
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
