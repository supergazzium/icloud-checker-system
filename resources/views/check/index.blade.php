@extends('layouts.app')
@section('title', __('check.title'))
@section('content')
<div x-data="{
        service: {{ optional($services->first())->id ?? 'null' }},
        price: {{ optional($services->first())->sell_price ?? 0 }},
        imei: '',
        prices: {{ Illuminate\Support\Js::from($services->pluck('sell_price','id')) }},
        select(id){ this.service = id; this.price = this.prices[id]; },
        get valid(){ return this.service && this.imei.trim().length >= 8; }
     }">

    <div class="mb-7">
        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">{{ __('check.title') }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ app()->getLocale()==='th'
            ? 'ตรวจสถานะ iCloud (Find My), Activation Lock, MDM, Blacklist และ SIM Lock ของอุปกรณ์ Apple'
            : 'Verify iCloud (Find My), Activation Lock, MDM, Blacklist and SIM Lock status of Apple devices' }}</p>
    </div>

    <form method="POST" action="{{ route('check.store') }}" class="max-w-3xl">
        @csrf
        <input type="hidden" name="service_id" :value="service">

        <div class="bg-white border border-gray-200 rounded-2xl p-7 shadow-sm">

            {{-- Step 1 : service --}}
            <p class="text-sm font-semibold text-gray-700 mb-3">1. {{ __('check.select_service') }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-7">
                @foreach($services as $svc)
                <button type="button" @click="select({{ $svc->id }})"
                    :class="service === {{ $svc->id }} ? 'border-blue-600 bg-blue-50 ring-4 ring-blue-600/10' : 'border-gray-200 bg-white hover:border-blue-300'"
                    class="text-left border rounded-xl p-4 transition">
                    @php
                        $dt = $svc->device_type;
                        $iconMap = [
                            'macbook' => ['bg-green-100','text-green-600','fa-laptop'],
                            'ipad'    => ['bg-amber-100','text-amber-600','fa-tablet-screen-button'],
                            'iphone'  => ['bg-blue-100','text-blue-600','fa-mobile-screen'],
                            'all'     => ['bg-purple-100','text-purple-600','fa-shield-halved'],
                        ];
                        [$iconBg,$iconColor,$iconGlyph] = $iconMap[$dt] ?? $iconMap['iphone'];
                    @endphp
                    <div class="flex items-start justify-between gap-2">
                        <div class="w-9 h-9 rounded-lg {{ $iconBg }} flex items-center justify-center shrink-0">
                            <i class="fas {{ $iconGlyph }} {{ $iconColor }} text-sm"></i>
                        </div>
                        <span :class="service === {{ $svc->id }} ? 'bg-blue-600 border-transparent' : 'border-gray-300'"
                              class="w-5 h-5 rounded-full border flex items-center justify-center">
                            <i class="fas fa-check text-[10px] text-white" x-show="service === {{ $svc->id }}"></i>
                        </span>
                    </div>
                    <p class="mt-3 text-sm font-semibold text-gray-900 leading-snug">{{ $svc->name }}</p>
                    @if($svc->description)<p class="mt-1 text-xs text-gray-500">{{ $svc->description }}</p>@endif
                    <p class="mt-2.5 font-mono font-semibold text-blue-600">฿{{ number_format($svc->sell_price, 0) }}</p>
                </button>
                @endforeach
            </div>

            {{-- Step 2 : imei --}}
            <p class="text-sm font-semibold text-gray-700 mb-3">2. {{ __('check.enter_imei') }}</p>
            <div class="relative">
                <i class="fas fa-barcode absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="text" name="imei_serial" x-model="imei" spellcheck="false" autocomplete="off"
                    placeholder="{{ app()->getLocale()==='th' ? 'เช่น 353xxxxxxxxxxxx' : 'e.g. 353xxxxxxxxxxxx' }}"
                    class="w-full pl-11 pr-4 py-3.5 rounded-xl border border-gray-200 bg-gray-50 font-mono text-base tracking-wide
                           focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 outline-none transition">
            </div>
            @error('imei_serial')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            <p class="mt-2.5 flex items-start gap-2 text-xs text-gray-500 leading-relaxed">
                <i class="fas fa-circle-info mt-0.5 text-gray-400"></i>
                <span>{{ __('check.imei_hint') }}</span>
            </p>

            {{-- footer --}}
            <div class="flex items-center justify-between mt-6 pt-5 border-t border-gray-100">
                <p class="text-sm text-gray-500">
                    {{ app()->getLocale()==='th' ? 'ค่าบริการ' : 'Service fee' }}
                    <b class="text-gray-900 font-mono">฿<span x-text="price"></span></b>
                    · {{ app()->getLocale()==='th' ? 'ตัดจากเครดิตเมื่อตรวจสำเร็จ' : 'deducted on successful check' }}
                </p>
                <button type="submit" :disabled="!valid"
                    :class="valid ? 'bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-600/25 cursor-pointer' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                    class="inline-flex items-center gap-2 px-7 py-3 rounded-xl text-white text-sm font-semibold transition">
                    <i class="fas fa-search"></i> {{ __('check.check_btn') }}
                </button>
            </div>
        </div>
    </form>

</div>
@endsection
