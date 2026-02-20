<?php

namespace Kardal\Trace\Support;

use Illuminate\Support\Str;

class TraceIdGenerator
{
    /**
     * @return string
     */
    public static function make()
    {
        if (class_exists(Str::class) && method_exists(Str::class, 'uuid')) {
            return (string) Str::uuid();
        }

        try {
            return bin2hex(random_bytes(16));
        } catch (\Exception $e) {
            return str_replace('.', '', uniqid('', true));
        }
    }
}
