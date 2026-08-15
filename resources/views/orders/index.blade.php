@extends('layouts.app')
@section('title', __('app.order_history'))
@section('content')

<div class="flex items-center justify-between mb-7">
    <div>
        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">{{ __('app.order_history') }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ app()->getLocale()==='th' ? 'รายการตรวจสอบทั้งหมดของคุณ' : 'All your check records' }}</p>
    </div>
    <a href="{{ route('check.index') }}" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition">
        <i class="fas fa-search"></i> {{ __('check.check_btn') }}
    </a>
</div>

{{-- filters --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-3">
        <div class="relative flex-1 min-w-[220px]">
            <i class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="IMEI / Serial..."
                class="w-full border border-gray-300 rounded-xl pl-10 pr-4 py-2 text-sm focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 outline-none">
        </div>
        <select name="status" class="border border-gray-300 rounded-xl px-4 py-2 text-sm">
            <option value="">{{ app()->getLocale()==='th' ? 'ทุกสถานะ' : 'All status' }}</option>
            <option value="success"    {{ request('status')==='success'    ? 'selected':'' }}>{{ __('order.success') }}</option>
            <option value="error"      {{ request('status')==='error'      ? 'selected':'' }}>{{ __('order.error') }}</option>
            <option value="processing" {{ request('status')==='processing' ? 'selected':'' }}>{{ __('order.processing') }}</option>
        </select>
        <button class="bg-gray-800 hover:bg-gray-900 text-white px-5 py-2 rounded-xl text-sm font-medium transition">
            <i class="fas fa-filter mr-1"></i> {{ __('app.search') }}
        </button>
    </form>
</div>

{{-- table --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
    <table class="w-full text-sm min-w-[720px]">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left py-4 px-6 text-xs font-semibold text-gray-500">#</th>
                <th class="text-left py-4 px-4 text-xs font-semibold text-gray-500">{{ __('order.imei_serial') }}</th>
                <th class="text-left py-4 px-4 text-xs font-semibold text-gray-500">{{ __('order.service') }}</th>
                <th class="text-left py-4 px-4 text-xs font-semibold text-gray-500">{{ __('order.model') }}</th>
                <th class="text-left py-4 px-4 text-xs font-semibold text-gray-500">{{ app()->getLocale()==='th' ? 'สถานะ' : 'Status' }}</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500">{{ app()->getLocale()==='th' ? 'ราคา' : 'Price' }}</th>
                <th class="text-right py-4 px-6 text-xs font-semibold text-gray-500">{{ app()->getLocale()==='th' ? 'วันที่' : 'Date' }}</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($orders as $o)
            <tr class="hover:bg-blue-50/40 cursor-pointer transition" onclick="window.location='{{ route('orders.show', $o->id) }}'">
                <td class="py-3.5 px-6 text-gray-400">{{ $o->id }}</td>
                <td class="py-3.5 px-4 font-mono font-medium text-gray-900">{{ $o->imei_serial }}</td>
                <td class="py-3.5 px-4 text-gray-600">{{ $o->service->name ?? '-' }}</td>
                <td class="py-3.5 px-4 text-gray-600">{{ $o->result_model ?? '—' }}</td>
                <td class="py-3.5 px-4">
                    @php $s = $o->status; @endphp
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium
                        {{ $s==='success' ? 'bg-green-100 text-green-700' : ($s==='error' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                        {{ __('order.'.$s) }}
                    </span>
                </td>
                <td class="py-3.5 px-4 text-right font-mono font-semibold {{ $o->status === 'success' ? 'text-blue-600' : 'text-gray-300' }}">
                    {{ $o->status === 'success' ? '฿'.number_format($o->sell_price, 0) : '—' }}
                </td>
                <td class="py-3.5 px-6 text-right text-gray-400 text-xs">{{ $o->created_at->format('d/m/y H:i') }}</td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-14 text-gray-400">
                <i class="fas fa-inbox text-3xl mb-3 block opacity-40"></i>
                {{ app()->getLocale()==='th' ? 'ยังไม่มีรายการตรวจสอบ' : 'No checks yet' }}
            </td></tr>
            @endforelse
        </tbody>
    </table>
    </div>
    @if($orders->hasPages())<div class="p-4 border-t border-gray-100">{{ $orders->links() }}</div>@endif
</div>
@endsection
