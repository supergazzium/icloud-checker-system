@extends('layouts.app')
@section('title','2FA')
@section('content')
<div class="max-w-lg mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <div class="flex items-center gap-3 mb-4">
        <div class="w-10 h-10 rounded-full bg-green-100 text-green-600 flex items-center justify-center"><i class="fas fa-shield-check"></i></div>
        <div><h2 class="text-lg font-bold text-gray-900">2FA เปิดใช้งานอยู่</h2><p class="text-xs text-gray-500">บัญชีนี้ได้รับการป้องกันด้วยรหัสยืนยัน 2 ชั้น</p></div>
    </div>

    @if(session('recoveryCodes'))
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5">
        <p class="text-sm font-semibold text-amber-800 mb-2">รหัสสำรอง (ใช้ได้ครั้งเดียวต่อรหัส) — บันทึกเก็บไว้ให้ปลอดภัย จะไม่แสดงอีก:</p>
        <div class="grid grid-cols-2 gap-2 font-mono text-sm text-amber-900">
            @foreach(session('recoveryCodes') as $c)<span>{{ $c }}</span>@endforeach
        </div>
    </div>
    @endif

    <form method="POST" action="{{ route('admin.2fa.disable') }}" onsubmit="return confirm('ยืนยันปิดใช้งาน 2FA?')" class="flex gap-3">
        @csrf @method('delete')
        <input type="password" name="password" placeholder="รหัสผ่านปัจจุบัน" required class="flex-1 border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
        <button class="bg-red-600 hover:bg-red-700 text-white font-semibold px-5 py-2.5 rounded-xl text-sm">ปิดใช้งาน 2FA</button>
    </form>
</div>
@endsection
