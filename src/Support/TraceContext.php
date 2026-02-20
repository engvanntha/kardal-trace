<?php

namespace Kardal\Trace\Support;

class TraceContext
{
    /**
     * @var string|null
     */
    protected static $traceId;

    /**
     * @var string
     */
    protected static $source = 'none';

    /**
     * @param string $traceId
     * @param string $source
     * @return void
     */
    public static function set($traceId, $source = 'manual')
    {
        if (!is_string($traceId) || trim($traceId) === '') {
            return;
        }

        static::$traceId = trim($traceId);
        static::$source = $source;
    }

    /**
     * @param string|null $default
     * @return string|null
     */
    public static function get($default = null)
    {
        return static::$traceId ?: $default;
    }

    /**
     * @return string
     */
    public static function source()
    {
        return static::$source;
    }

    /**
     * @return void
     */
    public static function clear()
    {
        static::$traceId = null;
        static::$source = 'none';
    }
}
