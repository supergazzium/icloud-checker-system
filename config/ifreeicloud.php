<?php

return [
    'ifreeicloud' => [
        'key'      => env('IFREEICLOUD_KEY', ''),
        'username' => env('IFREEICLOUD_USERNAME', ''),
        'endpoint' => env('IFREEICLOUD_API_URL', 'https://api.ifreeicloud.co.uk'),
        'timeout'  => (int) env('IFREEICLOUD_TIMEOUT', 60),

        // Importer defaults. Both are overridable per-import in the UI.
        'usd_to_thb'     => (float) env('IFREEICLOUD_USD_TO_THB', 36.00),
        'default_markup' => (float) env('IFREEICLOUD_DEFAULT_MARKUP', 2.5),

        // How long to cache the provider service list. 1h keeps the admin
        // UI snappy without letting price drift stay hidden for too long.
        'servicelist_cache_seconds' => (int) env('IFREEICLOUD_SERVICELIST_CACHE', 3600),
    ],
];
