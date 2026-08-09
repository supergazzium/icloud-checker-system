@extends('layouts.app')
@section('title','ตั้งค่า 2FA')
@section('content')
<div class="max-w-lg mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h2 class="text-xl font-bold text-gray-900 mb-1">เปิดใช้งาน 2FA (Two-Factor Authentication)</h2>
    <p class="text-sm text-gray-500 mb-5">สแกน QR ด้วยแอป Google Authenticator หรือ Authy แล้วกรอกรหัส 6 หลักเพื่อยืนยัน</p>

    @if($errors->any())<p class="text-sm text-red-600 mb-4">{{ $errors->first() }}</p>@endif

    <div class="flex justify-center bg-gray-50 rounded-xl p-6 mb-4">{!! $qrSvg !!}</div>
    <p class="text-xs text-gray-400 text-center mb-6">หรือกรอกรหัสด้วยมือ: <span class="font-mono font-bold text-gray-700">{{ $secret }}</span></p>

    <form method="POST" action="{{ route('admin.2fa.enable') }}" class="flex gap-3">
        @csrf
        <input type="text" name="code" maxlength="6" inputmode="numeric" placeholder="123456" required
               class="flex-1 border border-gray-200 rounded-lg px-3 py-2.5 text-center text-lg tracking-widest font-mono">
        <button class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-2.5 rounded-xl">ยืนยัน</button>
    </form>
</div>
@endsection
