@extends('layouts.app')
@section('title', __('Profile'))
@section('content')
<div class="max-w-2xl mx-auto space-y-6">

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 text-green-700 rounded-xl px-4 py-3 text-sm">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">ข้อมูลโปรไฟล์</h3>
        <form method="POST" action="{{ route('profile.update') }}" class="space-y-4">
            @csrf
            @method('patch')
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">ชื่อ</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
                @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">อีเมล</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
                @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm">บันทึก</button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-semibold text-gray-900 mb-4">เปลี่ยนรหัสผ่าน</h3>
        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            @method('put')
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">รหัสผ่านปัจจุบัน</label>
                <input type="password" name="current_password" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
                @error('current_password', 'updatePassword') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">รหัสผ่านใหม่</label>
                <input type="password" name="password" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
                @error('password', 'updatePassword') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-600 mb-1">ยืนยันรหัสผ่านใหม่</label>
                <input type="password" name="password_confirmation" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm">เปลี่ยนรหัสผ่าน</button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">อุปกรณ์ที่ล็อกอินอยู่</h3>
            @if($sessions->count() > 1)
            <form method="POST" action="{{ route('profile.sessions.destroy-others') }}" onsubmit="return confirm('ออกจากระบบทุกอุปกรณ์อื่น?')">
                @csrf
                @method('delete')
                <button class="text-xs font-semibold text-red-600 hover:underline">ออกจากระบบอุปกรณ์อื่นทั้งหมด</button>
            </form>
            @endif
        </div>
        @if($sessions->isEmpty())
            <p class="text-sm text-gray-400">ไม่พบข้อมูล (ต้องตั้งค่า SESSION_DRIVER=database)</p>
        @else
        <div class="space-y-3">
            @foreach($sessions as $s)
            <div class="flex items-center justify-between border border-gray-100 rounded-xl p-3">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center"><i class="fas fa-desktop text-sm"></i></div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900">{{ $s->device }} @if($s->is_current)<span class="text-xs text-green-600 font-bold">· อุปกรณ์นี้</span>@endif</p>
                        <p class="text-xs text-gray-400">{{ $s->ip }} · {{ $s->last_activity->diffForHumans() }}</p>
                    </div>
                </div>
                @if(!$s->is_current)
                <form method="POST" action="{{ route('profile.sessions.destroy',$s->id) }}">
                    @csrf
                    @method('delete')
                    <button class="text-xs text-red-600 hover:underline">ออกจากระบบ</button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-red-100 p-6">
        <h3 class="font-semibold text-red-700 mb-4">ลบบัญชี</h3>
        <p class="text-sm text-gray-500 mb-4">เมื่อลบบัญชีแล้ว ข้อมูลทั้งหมดจะถูกลบอย่างถาวรและไม่สามารถกู้คืนได้</p>
        <form method="POST" action="{{ route('profile.destroy') }}" onsubmit="return confirm('ยืนยันการลบบัญชี?')" class="flex gap-3">
            @csrf
            @method('delete')
            <input type="password" name="password" placeholder="รหัสผ่าน" required class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm">ลบบัญชี</button>
        </form>
    </div>
</div>
@endsection
