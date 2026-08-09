<?php
namespace App\Http\Middleware;
use Closure;

class SetLocale {
    public function handle($request, Closure $next) {
        $locale = session('locale', auth()->user()?->locale ?? config('app.locale'));
        app()->setLocale(in_array($locale, ['th','en']) ? $locale : 'th');
        return $next($request);
    }
}
