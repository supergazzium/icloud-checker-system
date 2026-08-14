<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ForcePasswordChange
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user === null || ! ($user->must_change_password ?? false)) {
            return $next($request);
        }

        // Allow the profile-edit page, the password-update action, logout, and
        // the language switcher — everything else redirects to profile.edit.
        $allowedRoutes = [
            'profile.edit',
            'password.update',
            'logout',
            'lang.switch',
        ];
        if (in_array($request->route()?->getName(), $allowedRoutes, true)) {
            return $next($request);
        }

        return redirect()
            ->route('profile.edit')
            ->with('warning', __('You must set a new password before continuing.'));
    }
}
