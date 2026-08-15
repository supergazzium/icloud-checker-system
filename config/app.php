<?php

declare(strict_types=1);

/**
 * Minimal app-config override. The vendor default hardcodes `timezone`
 * to 'UTC' rather than reading from env, so we explicitly re-wire it
 * here. Every other key stays at the framework default.
 */
return [
    'timezone'        => env('APP_TIMEZONE', 'UTC'),
    'locale'          => env('APP_LOCALE', 'en'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),
];
