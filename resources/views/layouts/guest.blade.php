<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'iPart Store') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans text-gray-900 antialiased">
    <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
        <div class="flex flex-col items-center mb-4">
            <img src="{{ asset('images/logo-full.png') }}" alt="iPart Store" class="w-40 object-contain">
            <p class="text-xs text-gray-500 mt-1">iCloud · IMEI Checker</p>
        </div>
        <div class="w-full sm:max-w-md mt-2 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg">
            {{ $slot }}
        </div>
        <p class="text-xs text-gray-400 mt-6">
            <a href="{{ route('legal.terms') }}" class="hover:underline">ข้อตกลงการใช้บริการ</a> ·
            <a href="{{ route('legal.privacy') }}" class="hover:underline">นโยบายความเป็นส่วนตัว</a>
        </p>
    </div>
</body>
</html>
