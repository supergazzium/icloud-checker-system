<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

$app = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*', headers:
            Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );

        $middleware->alias([
            'admin'          => \App\Http\Middleware\AdminMiddleware::class,
            'active.user'    => \App\Http\Middleware\ActiveUserMiddleware::class,
            'set.locale'     => \App\Http\Middleware\SetLocale::class,
            '2fa'            => \App\Http\Middleware\RequireTwoFactor::class,
            'password.change'=> \App\Http\Middleware\ForcePasswordChange::class,
        ]);
        $middleware->append(\App\Http\Middleware\ForceHttps::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

// Production provisions the schema from database/schema.sql, so the test suite
// gets its framework tables from database/testing-migrations via this provider,
// registered only under the testing environment. Read APP_ENV directly — the
// application isn't booted yet here, so $app->environment() can't resolve.
if (($_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? env('APP_ENV')) === 'testing') {
    $app->register(\App\Providers\TestingMigrationServiceProvider::class);
}

return $app;
