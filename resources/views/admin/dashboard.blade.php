@extends('layouts.app')
@section('title', 'Admin Dashboard')
@section('content')
<h2 class="text-2xl font-bold text-gray-900 mb-6">Admin Dashboard</h2>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-500 mb-1">ผู้ใช้งานทั้งหมด</p>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_users'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-500 mb-1">คำสั่งตรวจทั้งหมด</p>
        <p class="text-2xl font-bold text-gray-900">{{ $stats['total_orders'] }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-500 mb-1">รายได้รวม</p>
        <p class="text-2xl font-bold text-green-600">฿{{ number_format($stats['total_revenue'],2) }}</p>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
        <p class="text-xs text-gray-500 mb-1">กำไรสุทธิ</p>
        <p class="text-2xl font-bold text-blue-600">฿{{ number_format($stats['total_profit'],2) }}</p>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">คำสั่งตรวจล่าสุด</h3>
            <a href="{{ route('admin.orders.index') }}" class="text-sm text-blue-600 hover:underline">ดูทั้งหมด</a>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($recentOrders as $order)
            <a href="{{ route('admin.orders.show',$order) }}" class="flex items-center justify-between px-6 py-3.5 hover:bg-gray-50">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ optional($order->user)->name ?? '—' }}</p>
                    <p class="text-xs text-gray-500 font-mono">{{ $order->imei_serial }} · {{ optional($order->service)->name }}</p>
                </div>
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $order->status==='success' ? 'bg-green-100 text-green-700' : ($order->status==='error' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-600') }}">
                    {{ $order->status }}
                </span>
            </a>
            @empty
            <p class="px-6 py-8 text-center text-sm text-gray-400">ยังไม่มีรายการ</p>
            @endforelse
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-900">ผู้ใช้ที่ใช้จ่ายสูงสุด</h3>
        </div>
        <div class="divide-y divide-gray-100">
            @forelse($topUsers as $u)
            <div class="flex items-center justify-between px-6 py-3.5">
                <div>
                    <p class="text-sm font-medium text-gray-900">{{ $u->name }}</p>
                    <p class="text-xs text-gray-400">{{ $u->orders_count }} รายการ</p>
                </div>
                <span class="text-sm font-bold text-blue-600">฿{{ number_format($u->spent ?? 0,2) }}</span>
            </div>
            @empty
            <p class="px-6 py-8 text-center text-sm text-gray-400">ยังไม่มีข้อมูล</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
