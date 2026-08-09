<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>{{ config('app.name') }} - @yield('title')</title>

    {{-- PWA --}}
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#2563eb">
    <meta name="mobile-web-app-capable" content="yes">
    {{-- iOS: Add to Home Screen --}}
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="iPart Store">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/icon-192.png') }}">
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/sw.js'));
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="h-full" x-data="{ sidebar: false }">
<div class="flex h-full">
    {{-- Mobile top bar --}}
    <div class="lg:hidden fixed top-0 inset-x-0 h-14 bg-white border-b z-30 flex items-center gap-3 px-4">
        <button @click="sidebar = true" class="w-10 h-10 flex items-center justify-center text-gray-600 hover:text-blue-600">
            <i class="fas fa-bars text-lg"></i>
        </button>
        <img src="{{ asset('images/logo.jpg') }}" alt="iPart Store" class="w-8 h-8 rounded-lg object-cover">
        <span class="font-bold text-gray-900">iPart Store</span>
        <span class="ml-auto text-sm font-semibold text-blue-600">฿{{ number_format(auth()->user()->balance,0) }}</span>
    </div>

    {{-- Backdrop (mobile) --}}
    <div x-show="sidebar" x-transition.opacity @click="sidebar = false" class="lg:hidden fixed inset-0 bg-black/40 z-30" style="display:none"></div>

    {{-- Sidebar --}}
    <div :class="sidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
         class="w-64 bg-white shadow-lg flex flex-col fixed h-full z-40 transition-transform duration-200 -translate-x-full lg:translate-x-0">
        <button @click="sidebar = false" class="lg:hidden absolute top-4 right-3 w-8 h-8 flex items-center justify-center text-gray-400 hover:text-gray-700">
            <i class="fas fa-xmark"></i>
        </button>
        <div class="p-5 border-b flex items-center gap-3">
            <img src="{{ asset('images/logo.jpg') }}" alt="iPart Store" class="w-10 h-10 rounded-xl object-cover">
            <div>
                <h1 class="font-bold text-gray-900">iPart Store</h1>
                <p class="text-xs text-gray-500">iCloud · IMEI Checker</p>
            </div>
        </div>
        <div class="mx-4 mt-4 bg-gradient-to-r from-blue-500 to-blue-700 rounded-xl p-4 text-white">
            <p class="text-xs opacity-75">{{ __('app.balance') }}</p>
            <p class="text-2xl font-bold">฿{{ number_format(auth()->user()->balance,2) }}</p>
            <a href="{{ route('credits.index') }}" class="text-xs bg-white/20 hover:bg-white/30 px-3 py-1 rounded-full mt-2 inline-block">+ {{ __('app.topup') }}</a>
        </div>
        <nav class="flex-1 p-4 space-y-1 overflow-y-auto mt-2">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }} transition">
                <i class="fas fa-chart-pie w-5"></i>{{ __('app.dashboard') }}</a>
            <a href="{{ route('check.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('check.*') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }} transition">
                <i class="fas fa-search w-5"></i>{{ __('app.check_imei') }}</a>
            <a href="{{ route('orders.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('orders.*') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }} transition">
                <i class="fas fa-list-alt w-5"></i>{{ __('app.order_history') }}</a>
            <a href="{{ route('credits.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('credits.*') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }} transition">
                <i class="fas fa-coins w-5"></i>{{ __('app.credits') }}</a>
            @if(auth()->user()->isAdmin())
            <p class="text-xs font-semibold text-gray-400 uppercase px-1 pt-4 pb-1">Admin</p>
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }} transition">
                <i class="fas fa-tachometer-alt w-5"></i>Admin Dashboard</a>
            <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.users.*') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }} transition">
                <i class="fas fa-users w-5"></i>{{ __('app.users') }}</a>
            <a href="{{ route('admin.services.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.services.*') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }} transition">
                <i class="fas fa-cogs w-5"></i>{{ __('app.services') }}</a>
            <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.orders.*') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }} transition">
                <i class="fas fa-receipt w-5"></i>{{ __('app.orders') }}</a>
            <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.settings.*') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }} transition">
                <i class="fas fa-sliders-h w-5"></i>{{ __('app.settings') }}</a>
            <a href="{{ route('admin.audit.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.audit.*') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }} transition">
                <i class="fas fa-clipboard-list w-5"></i>Audit Log</a>
            <a href="{{ route('admin.2fa.show') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-lg {{ request()->routeIs('admin.2fa.show') ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-blue-50 hover:text-blue-600' }} transition">
                <i class="fas fa-shield-halved w-5"></i>2FA {{ auth()->user()->two_factor_enabled ? '✓' : '' }}</a>
            @endif
        </nav>
        <div class="p-4 border-t">
            <div class="flex gap-2 mb-3">
                <a href="{{ route('lang.switch','th') }}" class="flex-1 text-center py-1.5 rounded-lg text-sm font-medium {{ app()->getLocale()==='th' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600' }}">🇹🇭 ไทย</a>
                <a href="{{ route('lang.switch','en') }}" class="flex-1 text-center py-1.5 rounded-lg text-sm font-medium {{ app()->getLocale()==='en' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-600' }}">🇬🇧 EN</a>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                    <i class="fas fa-user text-blue-600 text-sm"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-900 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">{{ auth()->user()->role }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf
                    <button class="text-gray-400 hover:text-red-500"><i class="fas fa-sign-out-alt"></i></button>
                </form>
            </div>
        </div>
    </div>
    {{-- Content --}}
    <div class="flex-1 lg:ml-64 overflow-auto pt-14 lg:pt-0">
        @if(session('success'))<div class="bg-green-500 text-white px-6 py-3 text-sm flex items-center gap-2"><i class="fas fa-check-circle"></i>{{ session('success') }}</div>@endif
        @if(session('error'))<div class="bg-red-500 text-white px-6 py-3 text-sm flex items-center gap-2"><i class="fas fa-exclamation-circle"></i>{{ session('error') }}</div>@endif
        <div class="p-4 sm:p-6 lg:p-8">@yield('content')</div>
    </div>
</div>
</body>
</html>