@extends('layouts.app')
@section('title','Services')
@section('content')
<div class="flex items-center justify-between mb-8">
    <h2 class="text-2xl font-bold text-gray-900">{{ __('app.services') }}</h2>
    <div class="flex gap-2">
        <a href="{{ route('admin.services.import.index') }}"
           class="bg-white border border-blue-200 text-blue-700 hover:bg-blue-50 px-5 py-2.5 rounded-xl font-semibold transition">
            <i class="fas fa-download mr-2"></i>{{ __('app.import_from_provider') }}
        </a>
        <button onclick="document.getElementById('addServiceModal').classList.remove('hidden')"
            class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition">
            <i class="fas fa-plus mr-2"></i>เพิ่มบริการ
        </button>
    </div>
</div>
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left py-4 px-6 text-xs font-semibold text-gray-500 uppercase">บริการ</th>
                <th class="text-center py-4 px-4 text-xs font-semibold text-gray-500 uppercase">Service ID</th>
                <th class="text-center py-4 px-4 text-xs font-semibold text-gray-500 uppercase">อุปกรณ์</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500 uppercase">ต้นทุน</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500 uppercase">ราคาขาย</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500 uppercase">กำไร</th>
                <th class="text-center py-4 px-4 text-xs font-semibold text-gray-500 uppercase">สถานะ</th>
                <th class="py-4 px-4"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @foreach($services as $svc)
            <tr class="hover:bg-gray-50">
                <td class="py-4 px-6">
                    <p class="font-semibold text-gray-900">{{ $svc->name_th }}</p>
                    <p class="text-xs text-gray-500">{{ $svc->name_en }}</p>
                </td>
                <td class="py-4 px-4 text-center">
                    <code class="bg-gray-100 px-2 py-1 rounded text-sm">{{ $svc->provider_service_id }}</code>
                </td>
                <td class="py-4 px-4 text-center">
                    <span class="px-2 py-1 rounded-full text-xs bg-blue-100 text-blue-800">{{ strtoupper($svc->device_type) }}</span>
                </td>
                <td class="py-4 px-4 text-right text-sm text-gray-600">฿{{ number_format($svc->cost_price,2) }}</td>
                <td class="py-4 px-4 text-right font-bold text-blue-600">฿{{ number_format($svc->sell_price,2) }}</td>
                <td class="py-4 px-4 text-right font-bold text-green-600">฿{{ number_format($svc->sell_price - $svc->cost_price,2) }}</td>
                <td class="py-4 px-4 text-center">
                    <span class="px-2 py-1 rounded-full text-xs {{ $svc->active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">{{ $svc->active ? 'Active' : 'Off' }}</span>
                </td>
                <td class="py-4 px-4 text-right">
                    <form method="POST" action="{{ route('admin.services.toggle',$svc->id) }}" class="inline">
                        @csrf
                        <button class="text-sm text-gray-500 hover:text-gray-700">{{ $svc->active ? 'ปิด' : 'เปิด' }}</button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

{{-- Add Service Modal --}}
<div id="addServiceModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 overflow-y-auto py-10">
    <div class="bg-white rounded-2xl p-6 w-full max-w-lg">
        <h3 class="font-bold text-gray-900 mb-4">เพิ่มบริการใหม่</h3>
        <form method="POST" action="{{ route('admin.services.store') }}" class="space-y-3">
            @csrf
            <div class="grid grid-cols-2 gap-3">
                <input type="text" name="name_th" placeholder="ชื่อ (ไทย)" required class="border rounded-xl px-4 py-3 text-sm">
                <input type="text" name="name_en" placeholder="Name (EN)" required class="border rounded-xl px-4 py-3 text-sm">
            </div>
            <input type="number" name="provider_service_id" placeholder="Service ID (เช่น 242)" required class="w-full border rounded-xl px-4 py-3 text-sm">
            <select name="device_type" class="w-full border rounded-xl px-4 py-3 text-sm">
                <option value="iphone">iPhone</option>
                <option value="ipad">iPad</option>
                <option value="macbook">MacBook</option>
                <option value="all">All</option>
            </select>
            <div class="grid grid-cols-2 gap-3">
                <input type="number" step="0.01" name="cost_price" placeholder="ต้นทุน (฿)" required class="border rounded-xl px-4 py-3 text-sm">
                <input type="number" step="0.01" name="sell_price" placeholder="ราคาขาย (฿)" required class="border rounded-xl px-4 py-3 text-sm">
            </div>
            <textarea name="description_th" placeholder="คำอธิบาย (ไทย)" class="w-full border rounded-xl px-4 py-3 text-sm" rows="2"></textarea>
            <textarea name="description_en" placeholder="Description (EN)" class="w-full border rounded-xl px-4 py-3 text-sm" rows="2"></textarea>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-semibold">บันทึก</button>
                <button type="button" onclick="document.getElementById('addServiceModal').classList.add('hidden')" class="flex-1 border py-3 rounded-xl font-semibold text-gray-600">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>
@endsection