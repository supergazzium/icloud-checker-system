@extends('layouts.app')
@section('title','All Orders')
@section('content')
<div class="flex items-center justify-between mb-8">
    <h2 class="text-2xl font-bold text-gray-900">{{ __('app.orders') }} (Admin)</h2>
    <a href="{{ route('admin.orders.export', request()->query()) }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-xl text-sm font-semibold inline-flex items-center gap-2">
        <i class="fas fa-file-csv"></i> Export CSV
    </a>
</div>
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="IMEI/Serial..."
            class="border border-gray-300 rounded-xl px-4 py-2 text-sm w-64">
        <select name="status" class="border border-gray-300 rounded-xl px-4 py-2 text-sm">
            <option value="">ทุกสถานะ</option>
            <option value="success" {{ request('status')==='success' ? 'selected':'' }}>Success</option>
            <option value="error"   {{ request('status')==='error'   ? 'selected':'' }}>Error</option>
        </select>
        <input type="date" name="date_from" value="{{ request('date_from') }}" class="border border-gray-300 rounded-xl px-4 py-2 text-sm">
        <input type="date" name="date_to" value="{{ request('date_to') }}" class="border border-gray-300 rounded-xl px-4 py-2 text-sm">
        <button class="bg-gray-700 text-white px-4 py-2 rounded-xl text-sm"><i class="fas fa-search mr-1"></i>ค้นหา</button>
    </form>
</div>
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left py-4 px-6 text-xs font-semibold text-gray-500">#</th>
                <th class="text-left py-4 px-4 text-xs font-semibold text-gray-500">ผู้ใช้</th>
                <th class="text-left py-4 px-4 text-xs font-semibold text-gray-500">IMEI/Serial</th>
                <th class="text-left py-4 px-4 text-xs font-semibold text-gray-500">บริการ</th>
                <th class="text-left py-4 px-4 text-xs font-semibold text-gray-500">สถานะ</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500">ราคา</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500">กำไร</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500">วันที่</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($orders as $o)
            <tr class="hover:bg-gray-50">
                <td class="py-3 px-6 text-gray-400">{{ $o->id }}</td>
                <td class="py-3 px-4 font-medium text-gray-900">{{ $o->user->name ?? '-' }}</td>
                <td class="py-3 px-4 font-mono text-gray-900">{{ $o->imei_serial }}</td>
                <td class="py-3 px-4 text-gray-600">{{ $o->service->name_en ?? '-' }}</td>
                <td class="py-3 px-4">
                    <span class="px-2 py-0.5 rounded-full text-xs {{ $o->status==='success' ? 'bg-green-100 text-green-800' : ($o->status==='error' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">{{ $o->status }}</span>
                </td>
                <td class="py-3 px-4 text-right font-semibold text-blue-600">฿{{ number_format($o->sell_price,2) }}</td>
                <td class="py-3 px-4 text-right font-semibold text-green-600">฿{{ number_format($o->profit,2) }}</td>
                <td class="py-3 px-4 text-right text-gray-400 text-xs">{{ $o->created_at->format('d/m/y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center py-10 text-gray-400">ไม่มีคำสั่ง</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($orders->hasPages())<div class="p-4 border-t">{{ $orders->links() }}</div>@endif
</div>
@endsection