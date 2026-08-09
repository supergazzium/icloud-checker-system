@extends('layouts.app')
@section('title', 'Audit Log')
@section('content')
<div class="flex items-center justify-between mb-6">
    <h2 class="text-2xl font-bold text-gray-900">Audit Log — เครดิตที่ Admin แก้ไข</h2>
    <form method="GET" class="flex gap-2">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหาผู้ใช้..." class="border border-gray-200 rounded-lg px-3 py-2 text-sm">
        <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold">ค้นหา</button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
            <tr>
                <th class="text-left px-5 py-3">เวลา</th>
                <th class="text-left px-5 py-3">Admin</th>
                <th class="text-left px-5 py-3">ผู้ใช้</th>
                <th class="text-right px-5 py-3">จำนวน</th>
                <th class="text-left px-5 py-3">รายละเอียด</th>
                <th class="text-left px-5 py-3">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse($logs as $log)
            <tr class="hover:bg-gray-50">
                <td class="px-5 py-3 text-gray-500 whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                <td class="px-5 py-3 font-semibold text-gray-900">{{ $log->admin->name ?? '—' }}</td>
                <td class="px-5 py-3">{{ $log->user->name ?? '—' }} <span class="text-gray-400">({{ $log->user->email ?? '' }})</span></td>
                <td class="px-5 py-3 text-right font-bold {{ $log->amount >= 0 ? 'text-green-600' : 'text-red-600' }}">
                    {{ $log->amount >= 0 ? '+' : '' }}฿{{ number_format($log->amount,2) }}
                </td>
                <td class="px-5 py-3 text-gray-600">{{ $log->description }}</td>
                <td class="px-5 py-3 text-gray-400 font-mono text-xs">{{ $log->admin_ip ?? '—' }}</td>
            </tr>
            @empty
            <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400">ยังไม่มีประวัติ</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
