@extends('layouts.app')
@section('title','User: '.$user->name)
@section('content')
<div class="mb-6 flex items-center gap-4">
    <a href="{{ route('admin.users.index') }}" class="text-gray-400 hover:text-gray-600"><i class="fas fa-arrow-left"></i></a>
    <h2 class="text-2xl font-bold text-gray-900">{{ $user->name }}</h2>
    <span class="px-3 py-1 rounded-full text-xs {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $user->is_active ? 'Active' : 'Disabled' }}</span>
</div>
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Top-up Form --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">เติมเครดิต</h3>
        <p class="text-3xl font-bold text-blue-600 mb-4">฿{{ number_format($user->balance,2) }}</p>
        <form method="POST" action="{{ route('admin.users.topup',$user->id) }}" class="space-y-3">
            @csrf
            <input type="number" name="amount" placeholder="จำนวน (บาท)" min="1" step="0.01" required class="w-full border rounded-xl px-4 py-3 text-sm">
            <input type="text"   name="description" placeholder="หมายเหตุ (optional)" class="w-full border rounded-xl px-4 py-3 text-sm">
            <input type="text"   name="reference"   placeholder="เลขอ้างอิง (optional)" class="w-full border rounded-xl px-4 py-3 text-sm">
            <button class="w-full bg-green-600 text-white py-3 rounded-xl font-semibold hover:bg-green-700 transition">
                <i class="fas fa-plus mr-2"></i>เติมเครดิต
            </button>
        </form>
    </div>
    {{-- Credit History --}}
    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <h3 class="font-semibold text-gray-900 p-5 border-b">ประวัติเครดิต</h3>
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <th class="text-left py-3 px-5 text-xs font-semibold text-gray-500">ประเภท</th>
                    <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500">จำนวน</th>
                    <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500">คงเหลือ</th>
                    <th class="text-right py-3 px-4 text-xs font-semibold text-gray-500">วันที่</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($transactions as $tx)
                <tr class="hover:bg-gray-50">
                    <td class="py-3 px-5">
                        <span class="px-2 py-0.5 rounded-full text-xs {{ $tx->type==='topup' ? 'bg-green-100 text-green-800' : ($tx->type==='refund' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">{{ $tx->type }}</span>
                        <span class="text-gray-500 ml-2 text-xs">{{ $tx->description }}</span>
                    </td>
                    <td class="py-3 px-4 text-right font-bold {{ $tx->type==='deduct' ? 'text-red-600' : 'text-green-600' }}">
                        {{ $tx->type==='deduct' ? '-' : '+' }}฿{{ number_format($tx->amount,2) }}
                    </td>
                    <td class="py-3 px-4 text-right text-gray-900">฿{{ number_format($tx->balance_after,2) }}</td>
                    <td class="py-3 px-4 text-right text-gray-400 text-xs">{{ $tx->created_at->format('d/m/y H:i') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($transactions->hasPages())<div class="p-4 border-t">{{ $transactions->links() }}</div>@endif
    </div>
</div>
@endsection