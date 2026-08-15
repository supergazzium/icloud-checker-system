@extends('layouts.app')
@section('title', 'Order #'.$order->id)
@section('content')
<div class="max-w-3xl mx-auto">
    <a href="{{ route('admin.orders.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-blue-600 mb-5 transition">
        <i class="fas fa-arrow-left"></i> All Orders
    </a>

    <div class="bg-white border border-gray-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="flex items-center justify-between px-6 py-5 border-b border-gray-100">
            <div>
                <p class="text-lg font-bold text-gray-900">Order #{{ $order->id }}</p>
                <p class="text-sm text-gray-500 mt-0.5">{{ $order->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $order->status==='success' ? 'bg-green-100 text-green-800' : ($order->status==='error' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                {{ strtoupper($order->status) }}
            </span>
        </div>

        <div class="grid sm:grid-cols-2 gap-x-8 gap-y-3 px-6 py-6">
            <div class="flex justify-between border-b border-gray-50 py-2"><span class="text-sm text-gray-500">ผู้ใช้</span><span class="text-sm font-bold text-gray-900">{{ $order->user->name ?? '-' }}</span></div>
            <div class="flex justify-between border-b border-gray-50 py-2"><span class="text-sm text-gray-500">อีเมล</span><span class="text-sm font-bold text-gray-900">{{ $order->user->email ?? '-' }}</span></div>
            <div class="flex justify-between border-b border-gray-50 py-2"><span class="text-sm text-gray-500">บริการ</span><span class="text-sm font-bold text-gray-900">{{ $order->service->name_th ?? '-' }}</span></div>
            <div class="flex justify-between border-b border-gray-50 py-2"><span class="text-sm text-gray-500">IMEI/Serial</span><span class="text-sm font-bold font-mono text-gray-900">{{ $order->imei_serial }}</span></div>
            <div class="flex justify-between border-b border-gray-50 py-2"><span class="text-sm text-gray-500">ราคาขาย</span><span class="text-sm font-bold text-blue-600">฿{{ number_format($order->sell_price,2) }}</span></div>
            <div class="flex justify-between border-b border-gray-50 py-2"><span class="text-sm text-gray-500">กำไร</span><span class="text-sm font-bold text-green-600">฿{{ number_format($order->profit,2) }}</span></div>
            <div class="flex justify-between border-b border-gray-50 py-2"><span class="text-sm text-gray-500">รุ่นเครื่อง</span><span class="text-sm font-bold text-gray-900">{{ $order->result_model ?? '-' }}</span></div>
            <div class="flex justify-between border-b border-gray-50 py-2"><span class="text-sm text-gray-500">Warranty</span><span class="text-sm font-bold text-gray-900">{{ $order->result_warranty ?? '-' }}</span></div>
        </div>

        @if($order->status === 'error')
        <div class="mx-6 mb-6 bg-red-50 border border-red-200 rounded-xl p-4">
            <p class="text-sm font-semibold text-red-800">Error message</p>
            <p class="text-sm text-red-600 mt-1">{{ $order->error_message ?? '—' }}</p>
        </div>
        @endif

        {{-- Raw upstream response — admins only. Useful for diagnosing
             provider-side rejections without another API round-trip. --}}
        @if($order->raw_response)
        <details class="mx-6 mb-6 bg-gray-50 border border-gray-200 rounded-xl overflow-hidden group">
            <summary class="p-4 cursor-pointer text-sm font-semibold text-gray-700 hover:bg-gray-100 select-none">
                <i class="fas fa-code mr-2"></i>Raw upstream response
                <span class="text-xs text-gray-400 font-normal ml-2">({{ strlen($order->raw_response) }} bytes)</span>
            </summary>
            <pre class="p-4 text-xs bg-gray-900 text-gray-100 overflow-auto max-h-96">{{ trim($order->raw_response) }}</pre>
        </details>
        @endif

        @php $alreadyRefunded = \App\Models\CreditTransaction::where('order_id',$order->id)->where('type','refund')->exists(); @endphp
        <div class="px-6 pb-6 flex flex-wrap gap-3">
            <a href="{{ route('orders.show',$order) }}" class="inline-flex items-center gap-2 bg-gray-100 hover:bg-gray-200 text-gray-700 px-5 py-2.5 rounded-xl text-sm font-semibold">
                <i class="fas fa-eye"></i> ดูหน้าผลตรวจแบบลูกค้า
            </a>
            @if($order->status !== 'success')
                @if($alreadyRefunded)
                <span class="inline-flex items-center gap-2 bg-gray-50 border border-gray-200 text-gray-400 px-5 py-2.5 rounded-xl text-sm font-semibold">
                    <i class="fas fa-check"></i> คืนเครดิตแล้ว
                </span>
                @else
                <form method="POST" action="{{ route('admin.orders.refund',$order) }}" onsubmit="return confirm('ยืนยันคืนเครดิต ฿{{ number_format($order->sell_price,2) }} ให้ {{ $order->user->name ?? '' }}?')">
                    @csrf
                    <button class="bg-amber-600 hover:bg-amber-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold">
                        <i class="fas fa-rotate-left mr-1"></i> คืนเครดิต ฿{{ number_format($order->sell_price,2) }}
                    </button>
                </form>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
