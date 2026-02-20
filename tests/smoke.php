<?php

class AppContainerStub
{
    public $instances = [];

    public function instance($key, $value)
    {
        $this->instances[$key] = $value;
    }
}

class HeaderBagStub
{
    public $headers = [];

    public function set($name, $value)
    {
        $this->headers[$name] = $value;
    }

    public function get($name)
    {
        return array_key_exists($name, $this->headers) ? $this->headers[$name] : null;
    }
}

class AttributeBagStub
{
    public $values = [];

    public function set($name, $value)
    {
        $this->values[$name] = $value;
    }

    public function get($name)
    {
        return array_key_exists($name, $this->values) ? $this->values[$name] : null;
    }
}

class HttpRequestStub
{
    public $attributes;
    private $headers;

    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
        $this->attributes = new AttributeBagStub();
    }

    public function header($name)
    {
        return array_key_exists($name, $this->headers) ? $this->headers[$name] : null;
    }
}

class HttpResponseStub
{
    public $headers;

    public function __construct()
    {
        $this->headers = new HeaderBagStub();
    }
}

class OutboundRequestStub
{
    public $headers = [];

    public function hasHeader($name)
    {
        return array_key_exists($name, $this->headers);
    }

    public function withHeader($name, $value)
    {
        $clone = clone $this;
        $clone->headers[$name] = $value;

        return $clone;
    }
}

class LogFacadeStub
{
    public static $context = [];

    public static function getFacadeRoot()
    {
        return new class {
            public function withContext(array $context)
            {
                LogFacadeStub::$context = array_merge(LogFacadeStub::$context, $context);
            }
        };
    }

    public static function withContext(array $context)
    {
        self::$context = array_merge(self::$context, $context);
    }
}

class_alias('LogFacadeStub', 'Illuminate\\Support\\Facades\\Log');

$GLOBALS['__trace_config'] = [];
$GLOBALS['__trace_app'] = new AppContainerStub();

function config($key = null, $default = null)
{
    if (is_array($key)) {
        foreach ($key as $k => $v) {
            $GLOBALS['__trace_config'][$k] = $v;
        }

        return null;
    }

    if ($key === null) {
        return $GLOBALS['__trace_config'];
    }

    return array_key_exists($key, $GLOBALS['__trace_config']) ? $GLOBALS['__trace_config'][$key] : $default;
}

function app($key = null)
{
    if ($key === null) {
        return $GLOBALS['__trace_app'];
    }

    return array_key_exists($key, $GLOBALS['__trace_app']->instances) ? $GLOBALS['__trace_app']->instances[$key] : null;
}

require __DIR__ . '/../src/Support/TraceContext.php';
require __DIR__ . '/../src/Support/TraceIdGenerator.php';
require __DIR__ . '/../src/Support/CurlTrace.php';
require __DIR__ . '/../src/Http/GuzzleTraceMiddleware.php';
require __DIR__ . '/../src/Http/Middleware/TraceMiddleware.php';
require __DIR__ . '/../src/helpers.php';

use Kardal\Trace\Http\GuzzleTraceMiddleware;
use Kardal\Trace\Http\Middleware\TraceMiddleware;
use Kardal\Trace\Support\CurlTrace;
use Kardal\Trace\Support\TraceContext;
use Kardal\Trace\Support\TraceIdGenerator;

$assertions = 0;
$assert = function ($condition, $message) use (&$assertions) {
    $assertions++;
    if (!$condition) {
        throw new RuntimeException('Assertion failed: ' . $message);
    }
};

TraceContext::clear();
$assert(TraceContext::get() === null, 'TraceContext starts empty');
TraceContext::set('abc-123', 'manual');
$assert(TraceContext::get() === 'abc-123', 'TraceContext set/get works');
$assert(TraceContext::source() === 'manual', 'TraceContext source works');
TraceContext::clear();

$id1 = TraceIdGenerator::make();
$id2 = TraceIdGenerator::make();
$assert(is_string($id1) && $id1 !== '', 'TraceIdGenerator returns string');
$assert($id1 !== $id2, 'TraceIdGenerator generates unique values');

$lines = CurlTrace::appendHeaderLines(['Accept: application/json'], 'trace-1', 'X-Correlation-Id');
$assert(in_array('X-Correlation-Id: trace-1', $lines, true), 'CurlTrace appends header');
$lines2 = CurlTrace::appendHeaderLines($lines, 'trace-2', 'X-Correlation-Id');
$matches = array_values(array_filter($lines2, function ($line) {
    return strpos($line, 'X-Correlation-Id:') === 0;
}));
$assert(count($matches) === 1, 'CurlTrace does not duplicate existing trace header');
$assert($matches[0] === 'X-Correlation-Id: trace-1', 'CurlTrace keeps existing trace header value');

$middlewareFactory = GuzzleTraceMiddleware::factory('X-Correlation-Id', 'trace-outbound-1');
$handler = $middlewareFactory(function ($request, array $options = []) {
    return ['request' => $request, 'options' => $options];
});
$result = $handler(new OutboundRequestStub(), []);
$assert($result['request']->headers['X-Correlation-Id'] === 'trace-outbound-1', 'GuzzleTraceMiddleware adds header');

$hdr = trace_headers('trace-helper-1', [], 'X-Correlation-Id');
$assert(isset($hdr['X-Correlation-Id']) && $hdr['X-Correlation-Id'] === 'trace-helper-1', 'trace_headers helper works');

TraceContext::clear();
$traceMiddleware = new TraceMiddleware();
$request = new HttpRequestStub([
    'X-Request-Id' => 'incoming-req-123',
]);
$response = $traceMiddleware->handle($request, function ($req) {
    return new HttpResponseStub();
});

$assert(TraceContext::get() === 'incoming-req-123', 'TraceMiddleware picks inbound request id');
$assert($request->attributes->get('correlation_id') === 'incoming-req-123', 'TraceMiddleware sets request attribute');
$assert($response->headers->get('X-Correlation-Id') === 'incoming-req-123', 'TraceMiddleware sets response header');
$assert(config('app.uuid') === 'incoming-req-123', 'TraceMiddleware syncs app.uuid');
$assert(LogFacadeStub::$context['correlation_id'] === 'incoming-req-123', 'TraceMiddleware sets log context');

TraceContext::clear();
$generatedResponse = $traceMiddleware->handle(new HttpRequestStub([]), function ($req) {
    return new HttpResponseStub();
});
$generated = $generatedResponse->headers->get('X-Correlation-Id');
$assert(is_string($generated) && $generated !== '', 'TraceMiddleware generates id when missing');

echo 'OK: ' . $assertions . " assertions passed\n";
