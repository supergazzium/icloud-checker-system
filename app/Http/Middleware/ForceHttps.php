<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

/** Redirects HTTP to HTTPS and sets HSTS in production. No-op locally so `php artisan serve` still works over http. */
class ForceHttps
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('production') && !$request->secure()) {
            return redirect()->secure($request->getRequestUri(), 301);
        }

        $response = $next($request);

        if (app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        return $response;
    }
}
