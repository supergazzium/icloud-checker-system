@extends('layouts.app')
@section('title','Users')
@section('content')
<div class="flex items-center justify-between mb-8">
    <h2 class="text-2xl font-bold text-gray-900">{{ __('app.users') }}</h2>
    <button onclick="document.getElementById('addUserModal').classList.remove('hidden')"
        class="bg-blue-600 text-white px-5 py-2.5 rounded-xl font-semibold hover:bg-blue-700 transition">
        <i class="fas fa-plus mr-2"></i>เพิ่มผู้ใช้
    </button>
</div>

{{-- Filter --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-6">
    <form method="GET" class="flex gap-3">
        <input type="text" name="search" value="{{ request('search') }}" placeholder="ค้นหา ชื่อ/อีเมล..."
            class="border border-gray-300 rounded-xl px-4 py-2 text-sm w-64 focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        <button class="bg-gray-700 text-white px-4 py-2 rounded-xl text-sm"><i class="fas fa-search mr-1"></i>ค้นหา</button>
    </form>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left py-4 px-6 text-xs font-semibold text-gray-500 uppercase">ชื่อ</th>
                <th class="text-left py-4 px-4 text-xs font-semibold text-gray-500 uppercase">อีเมล</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500 uppercase">เครดิต</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500 uppercase">คำสั่ง</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500 uppercase">ยอดใช้</th>
                <th class="text-center py-4 px-4 text-xs font-semibold text-gray-500 uppercase">สถานะ</th>
                <th class="py-4 px-4"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($users as $user)
            <tr class="hover:bg-gray-50">
                <td class="py-4 px-6">
                    <p class="font-semibold text-gray-900">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $user->role }}</p>
                </td>
                <td class="py-4 px-4 text-sm text-gray-600">{{ $user->email }}</td>
                <td class="py-4 px-4 text-right font-bold text-blue-600">฿{{ number_format($user->balance,2) }}</td>
                <td class="py-4 px-4 text-right text-sm text-gray-900">{{ number_format($user->orders_count) }}</td>
                <td class="py-4 px-4 text-right text-sm font-semibold text-gray-900">฿{{ number_format($user->total_spent ?? 0,2) }}</td>
                <td class="py-4 px-4 text-center">
                    <span class="px-2 py-1 rounded-full text-xs {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                        {{ $user->is_active ? 'Active' : 'Disabled' }}
                    </span>
                </td>
                <td class="py-4 px-4 text-right">
                    <a href="{{ route('admin.users.show', $user->id) }}" class="text-blue-500 hover:text-blue-700 text-sm font-medium mr-3">จัดการ</a>
                    <form method="POST" action="{{ route('admin.users.toggleActive',$user->id) }}" class="inline">
                        @csrf
                        <button class="text-gray-400 hover:text-gray-600 text-sm">{{ $user->is_active ? 'ปิด' : 'เปิด' }}</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center py-10 text-gray-400">ไม่มีผู้ใช้งาน</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($users->hasPages())<div class="p-4 border-t">{{ $users->links() }}</div>@endif
</div>

{{-- Add User Modal --}}
<div id="addUserModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-6 w-full max-w-md">
        <h3 class="font-bold text-gray-900 mb-4">เพิ่มผู้ใช้ใหม่</h3>
        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-4">
            @csrf
            <input type="text"     name="name"     placeholder="ชื่อ"     required class="w-full border rounded-xl px-4 py-3 text-sm">
            <input type="email"    name="email"    placeholder="อีเมล"    required class="w-full border rounded-xl px-4 py-3 text-sm">
            <input type="password" name="password" placeholder="รหัสผ่าน" required class="w-full border rounded-xl px-4 py-3 text-sm">
            <select name="role" class="w-full border rounded-xl px-4 py-3 text-sm">
                <option value="user">User</option>
                <option value="reseller">Reseller</option>
            </select>
            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-xl font-semibold">บันทึก</button>
                <button type="button" onclick="document.getElementById('addUserModal').classList.add('hidden')"
                    class="flex-1 border py-3 rounded-xl font-semibold text-gray-600">ยกเลิก</button>
            </div>
        </form>
    </div>
</div>
@endsection