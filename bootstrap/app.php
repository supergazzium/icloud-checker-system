<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'admin'       => \App\Http\Middleware\AdminMiddleware::class,
            'active.user' => \App\Http\Middleware\ActiveUserMiddleware::class,
            'set.locale'  => \App\Http\Middleware\SetLocale::class,
            '2fa'         => \App\Http\Middleware\RequireTwoFactor::class,
        ]);
        $middleware->append(\App\Http\Middleware\ForceHttps::class);
        $middleware->validateCsrfTokens(except: [
            'credits/topup/webhook',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
