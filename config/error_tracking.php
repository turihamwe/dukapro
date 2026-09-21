<?php

return [
    'enabled' => env('ERROR_TRACKING_ENABLED', true),

    /*
    | HTTP status codes (4xx) that represent a frustrating user experience.
    | All 5xx responses are always logged when they surface as exceptions.
    */
    'report_http_status_codes' => [
        403,
        404,
        405,
        408,
        413,
        419,
        429,
    ],

    'report_server_errors_from_status' => 500,

    /*
    | Store request IP (and Cloudflare country header when present) on each log.
    | IP can be personal data — disclose in your privacy policy and limit retention.
    */
    'store_ip' => env('ERROR_TRACKING_STORE_IP', true),

    /*
    | Delete rows older than this many days (see error-logs:prune). Schedule daily in production.
    */
    'retention_days' => (int) env('ERROR_TRACKING_RETENTION_DAYS', 90),

    /*
    | 404 dedupe: one stored row per URL path + business + environment within the TTL window.
    | Use redis/memcached for CACHE_DRIVER when running multiple app servers.
    */
    'dedupe_404' => [
        'enabled' => env('ERROR_TRACKING_DEDUPE_404', true),
        'ttl_seconds' => (int) env('ERROR_TRACKING_DEDUPE_404_TTL', 3600),
    ],

    /*
    | After dedupe, log this fraction of remaining 404s (0.0–1.0). 1.0 = log every first hit per window.
    */
    'sample_404_rate' => (float) env('ERROR_TRACKING_SAMPLE_404_RATE', 1.0),
];
