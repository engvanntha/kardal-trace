<?php

namespace Kardal\Trace;

use Illuminate\Support\ServiceProvider;
use Kardal\Trace\Logging\CustomizeMonolog;
use Kardal\Trace\Support\TraceContext;
use Kardal\Trace\Support\TraceIdGenerator;

class TraceServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/trace.php', 'trace');
    }

    /**
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/trace.php' => config_path('trace.php'),
            ], 'trace-config');
        }

        $this->configureLoggingTaps();
        $this->registerHttpClientTracing();
    }

    /**
     * @return void
     */
    protected function configureLoggingTaps()
    {
        if (!config('trace.auto_configure_logging_taps', true)) {
            return;
        }

        $channels = (array) config('trace.logging_channels', ['stack']);
        foreach ($channels as $channel) {
            $channel = trim((string) $channel);
            if ($channel === '') {
                continue;
            }

            $configKey = 'logging.channels.' . $channel;
            $channelConfig = config($configKey);
            if (!is_array($channelConfig)) {
                continue;
            }

            $tapClass = CustomizeMonolog::class;
            $taps = isset($channelConfig['tap']) && is_array($channelConfig['tap'])
                ? $channelConfig['tap']
                : [];

            if (!in_array($tapClass, $taps, true)) {
                $taps[] = $tapClass;
            }

            $channelConfig['tap'] = $taps;
            config([$configKey => $channelConfig]);
        }
    }

    /**
     * @return void
     */
    protected function registerHttpClientTracing()
    {
        if (!class_exists('\Illuminate\Http\Client\PendingRequest')) {
            return;
        }

        \Illuminate\Http\Client\PendingRequest::macro('withTrace', function ($traceId = null, $headerName = null) {
            $traceId = $traceId ?: TraceContext::get();
            if (!$traceId) {
                $traceId = TraceIdGenerator::make();
                TraceContext::set($traceId, 'http-client');
            }

            $headerName = is_string($headerName) && trim($headerName) !== ''
                ? trim($headerName)
                : config('trace.header_name', 'X-Correlation-Id');

            return $this->withHeaders([$headerName => $traceId]);
        });

        if (!class_exists('\Illuminate\Support\Facades\Http')) {
            return;
        }

        if (!config('trace.enable_http_client_global_middleware', true)) {
            return;
        }

        $factory = \Illuminate\Support\Facades\Http::getFacadeRoot();
        if (!$factory || !method_exists($factory, 'globalRequestMiddleware')) {
            return;
        }

        \Illuminate\Support\Facades\Http::globalRequestMiddleware(function ($request) {
            $traceId = TraceContext::get();
            if (!$traceId) {
                $traceId = TraceIdGenerator::make();
                TraceContext::set($traceId, 'http-client');
            }

            $headerName = config('trace.header_name', 'X-Correlation-Id');
            if (is_object($request) && method_exists($request, 'hasHeader') && !$request->hasHeader($headerName)) {
                return $request->withHeader($headerName, $traceId);
            }

            return $request;
        });
    }
}
