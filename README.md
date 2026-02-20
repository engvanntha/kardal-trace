# engvanntha/kardal-trace

Shared trace propagation package for any Laravel project.

Supports Laravel `5.8` to `11.x`.

## Install

Option A: local monorepo path repository

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "../packages/kardal-trace",
      "options": {
        "symlink": true
      }
    }
  ],
  "require": {
    "engvanntha/kardal-trace": "^1.0"
  }
}
```

Option B: install from Git repository (recommended for other projects)

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "git@github.com:engvanntha/kardal-trace.git"
    }
  ],
  "require": {
    "engvanntha/kardal-trace": "^1.0"
  }
}
```

Then run:

```bash
composer update engvanntha/kardal-trace --with-all-dependencies
```

## Register middleware

Laravel 11 (`bootstrap/app.php`)

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->append(\Kardal\Trace\Http\Middleware\TraceMiddleware::class);
})
```

Laravel 5.8-10 (`app/Http/Kernel.php`)

```php
protected $middleware = [
    // ...
    \Kardal\Trace\Http\Middleware\TraceMiddleware::class,
];
```

## What it does

- Extracts incoming trace/correlation/request id headers.
- Generates one when absent.
- Stores the id in request context (`correlation_id` by default).
- Adds the trace id to response header (`X-Correlation-Id` by default).
- Injects trace id into logs via Monolog tap processor.
- Provides helper APIs for outbound `Http`, `Guzzle`, and `curl` requests.

## Header priority

By default inbound headers are checked in this order:

1. `X-Correlation-Id`
2. `X-Request-Id`
3. `X-Trace-Id`

## Outbound usage

Laravel Http Client:

```php
Http::withTrace()->post($url, $payload);
```

Guzzle:

```php
$stack = \GuzzleHttp\HandlerStack::create();
$stack->push(trace_guzzle_middleware());
$client = new \GuzzleHttp\Client(['handler' => $stack]);
```

cURL:

```php
$headers = trace_curl_headers(['Accept: application/json']);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
```

## Config (optional)

Publish config file:

```bash
php artisan vendor:publish --tag=trace-config
```
# kardal-trace
