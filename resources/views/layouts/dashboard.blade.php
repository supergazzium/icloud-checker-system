@extends('layouts.app')
@section('title', __('app.dashboard'))
@section('content')
<h2 class="text-2xl font-bold text-gray-900 mb-6">{{ __('app.dashboard') }}</h2>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-500 mb-1">{{ __('app.balance') }}</p>
        <p class="text-2xl font-bold text-blue-600">฿{{ number_format($stats['balance'],2) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-500 mb-1">{{ __('app.total_orders') ?? 'รายการทั้งหมด' }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_orders'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-500 mb-1">{{ __('app.success_orders') ?? 'สำเร็จ' }}</p>
        <p class="text-2xl font-bold text-green-600">{{ $stats['success_orders'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-500 mb-1">{{ __('app.error_orders') ?? 'ผิดพลาด' }}</p>
        <p class="text-2xl font-bold text-red-600">{{ $stats['error_orders'] }}</p>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">{{ __('app.order_history') }}</h3>
            <a href="{{ route('orders.index') }}" class="text-sm text-blue-600 hover:underline">{{ __('app.view_all') ?? 'ดูทั้งหมด' }}</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($recentOrders as $order)
            <a href="{{ route('orders.show',$order) }}" class="flex items-center justify-between px-6 py-3.5 hover:bg-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ optional($order->service)->name ?? '—' }}</p>
                    <p class="text-xs text-gray-500 font-mono">{{ $order->imei_serial }}</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $order->status==='success' ? 'bg-green-100 text-green-700' : ($order->status==='error' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                    {{ $order->status }}
                </span>
            </a>
            @empty
            <p class="px-6 py-8 text-center text-sm text-gray-400">{{ __('app.no_data') ?? 'ยังไม่มีรายการ' }}</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">{{ __('app.credits') }}</h3>
            <a href="{{ route('credits.index') }}" class="text-sm text-blue-600 hover:underline">{{ __('app.view_all') ?? 'ดูทั้งหมด' }}</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($recentCredits as $tx)
            <div class="flex items-center justify-between px-6 py-3.5">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $tx->description }}</p>
                    <p class="text-xs text-gray-400">{{ $tx->created_at->format('d/m/Y H:i') }}</p>
                </div>
                <span class="text-sm font-bold {{ $tx->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $tx->amount >= 0 ? '+' : '' }}฿{{ number_format($tx->amount,2) }}
                </span>
            </div>
            @empty
            <p class="px-6 py-8 text-center text-sm text-gray-400">{{ __('app.no_data') ?? 'ยังไม่มีรายการ' }}</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
