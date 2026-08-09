@extends('layouts.app')
@section('title','ยืนยันตัวตน 2FA')
@section('content')
<div class="max-w-sm mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-10">
    <div class="text-center mb-5">
        <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mx-auto mb-3"><i class="fas fa-shield-halved text-2xl"></i></div>
        <h2 class="text-lg font-bold text-gray-900">ยืนยันตัวตนสองชั้น</h2>
        <p class="text-sm text-gray-500 mt-1">กรอกรหัส 6 หลักจากแอป Authenticator หรือรหัสสำรอง</p>
    </div>

    @if($errors->any())<p class="text-sm text-red-600 mb-3 text-center">{{ $errors->first() }}</p>@endif

    <form method="POST" action="{{ route('admin.2fa.verify') }}">
        @csrf
        <input type="text" name="code" maxlength="20" required autofocus placeholder="123456"
               class="w-full border border-gray-200 rounded-lg px-3 py-3 text-center text-lg tracking-widest font-mono mb-4">
        <button class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl">ยืนยัน</button>
    </form>
</div>
@endsection
