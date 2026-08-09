<?php
namespace App\Http\Middleware;
use Closure;

class ActiveUserMiddleware {
    public function handle($request, Closure $next) {
        if (auth()->check() && !auth()->user()->is_active) {
            auth()->logout();
            return redirect('/login')->with('error', __('auth.account_disabled'));
        }
        return $next($request);
    }
}
