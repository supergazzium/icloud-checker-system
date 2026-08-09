@extends('layouts.app')
@section('title','Settings')
@section('content')
<div class="mb-8"><h2 class="text-2xl font-bold text-gray-900">{{ __('app.settings') }}</h2></div>
<div class="max-w-xl bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
    <h3 class="font-semibold text-gray-900 mb-6 flex items-center gap-2">
        <i class="fas fa-key text-blue-600"></i> iFreeiCloud API Settings
    </h3>
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-semibold text-gray-700 mb-2">PHP API Key</label>
            <input type="text" name="ifreeicloud_key" value="{{ $apiKey }}"
                class="w-full border border-gray-300 rounded-xl px-4 py-3 font-mono text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        <div class="mb-6 p-4 bg-blue-50 rounded-xl text-sm text-blue-700">
            <i class="fas fa-info-circle mr-1"></i>
            API key นี้ใช้สำหรับเรียก ifreeicloud.co.uk — เก็บเป็นความลับ อย่าเปิดเผย
        </div>
        <button class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition">
            <i class="fas fa-save mr-2"></i>{{ __('app.save') }}
        </button>
    </form>
</div>
@endsection