<?php
namespace App\Http\Middleware;
use Closure;

/** Blocks admin routes until the 2FA challenge (if enabled for the account) is passed this session. */
class RequireTwoFactor
{
    public function handle($request, Closure $next)
    {
        $user = auth()->user();
        if ($user && $user->isAdmin() && $user->two_factor_enabled && !session('2fa_passed')) {
            if (!$request->routeIs('admin.2fa.*') && !$request->routeIs('logout')) {
                return redirect()->route('admin.2fa.challenge');
            }
        }
        return $next($request);
    }
}
