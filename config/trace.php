<?php

return [
    'header_name' => env('TRACE_HEADER_NAME', 'X-Correlation-Id'),
    'inbound_header_candidates' => array_filter(array_map('trim', explode(',', env('TRACE_INBOUND_HEADERS', 'X-Correlation-Id,X-Request-Id,X-Trace-Id')))),
    'response_header_name' => env('TRACE_RESPONSE_HEADER_NAME', 'X-Correlation-Id'),
    'request_attribute_key' => env('TRACE_REQUEST_ATTRIBUTE_KEY', 'correlation_id'),
    'log_context_key' => env('TRACE_LOG_CONTEXT_KEY', 'correlation_id'),
    'service_name' => env('TRACE_SERVICE_NAME', env('APP_NAME', 'app')),
    'log_service_key' => env('TRACE_LOG_SERVICE_KEY', 'service_name'),
    'enable_http_client_global_middleware' => env('TRACE_HTTP_GLOBAL_MIDDLEWARE', true),
    'auto_configure_logging_taps' => env('TRACE_AUTO_LOG_TAP', true),
    'logging_channels' => array_filter(array_map('trim', explode(',', env('TRACE_LOG_CHANNELS', 'stack,single,daily,stderr,custom_log,response_log,switchlog')))),
];
