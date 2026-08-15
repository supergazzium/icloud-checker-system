@extends('layouts.app')
@section('title', __('check.title'))
@section('content')
<div x-data="{
        service: {{ optional($services->first())->id ?? 'null' }},
        price: {{ optional($services->first())->sell_price ?? 0 }},
        imei: '',
        submitting: false,
        prices: {{ Illuminate\Support\Js::from($services->pluck('sell_price','id')) }},
        deviceTypes: {{ Illuminate\Support\Js::from($services->pluck('device_type','id')) }},
        select(id){ this.service = id; this.price = this.prices[id]; },
        // Normalise as the user types — strip separators + uppercase.
        // Mirrors the server-side normaliseSerial() exactly.
        normalise(){
            this.imei = (this.imei || '').replace(/[^A-Za-z0-9]/g,'').toUpperCase().slice(0,20);
        },
        classifySerial(v){
            const s = (v || '').toUpperCase();
            if (/^\d{15}$/.test(s)) return 'imei';
            if (/^[A-Z0-9]{8,14}$/.test(s) && /[A-Z]/.test(s)) return 'serial';
            return 'other';
        },
        // Standard Luhn checksum. Mirrors server-side luhnValid().
        luhnValid(number){
            let sum = 0;
            for (let i = 0; i < number.length; i++) {
                let d = parseInt(number[number.length - 1 - i], 10);
                if (i % 2 === 1) { d *= 2; if (d > 9) d -= 9; }
                sum += d;
            }
            return sum % 10 === 0;
        },
        get mismatchWarning(){
            const s = this.imei;
            if (s.length < 8) return null;
            const shape = this.classifySerial(s);
            const dt = this.deviceTypes[this.service];
            if (dt === 'macbook' && shape === 'imei')  return @js(__('check.mismatch_mac_needs_serial'));
            if (dt === 'ipad'    && shape === 'other') return @js(__('check.mismatch_ipad_needs_serial_or_imei'));
            if (dt === 'iphone'  && shape === 'other') return @js(__('check.mismatch_iphone_needs_serial_or_imei'));
            if (shape === 'imei' && !this.luhnValid(s)) return @js(__('check.imei_luhn_invalid'));
            return null;
        },
        get valid(){ return this.service && this.imei.length >= 8 && this.mismatchWarning === null && !this.submitting; }
     }">

    <div class="mb-7">
        <h2 class="text-2xl font-bold text-gray-900 tracking-tight">{{ __('check.title') }}</h2>
        <p class="mt-1 text-sm text-gray-500">{{ app()->getLocale()==='th'
            ? 'ตรวจสถานะ iCloud (Find My), Activation Lock, MDM, Blacklist และ SIM Lock ของอุปกรณ์ Apple'
            : 'Verify iCloud (Find My), Activation Lock, MDM, Blacklist and SIM Lock status of Apple devices' }}</p>
    </div>

    <form method="POST" action="{{ route('check.store') }}" class="max-w-3xl"
          @submit="submitting = true">
        @csrf
        <input type="hidden" name="service_id" :value="service">

        <div class="bg-white border border-gray-200 rounded-2xl p-7 shadow-sm">

            {{-- Step 1 : service --}}
            <p id="check-step-1" class="text-sm font-semibold text-gray-700 mb-3">1. {{ __('check.select_service') }}</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-7"
                 role="radiogroup" aria-labelledby="check-step-1">
                @foreach($services as $svc)
                <button type="button" @click="select({{ $svc->id }})"
                    role="radio"
                    :aria-checked="service === {{ $svc->id }} ? 'true' : 'false'"
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
                            <i class="fas {{ $iconGlyph }} {{ $iconColor }} text-sm" aria-hidden="true"></i>
                        </div>
                        <span :class="service === {{ $svc->id }} ? 'bg-blue-600 border-transparent' : 'border-gray-300'"
                              class="w-5 h-5 rounded-full border flex items-center justify-center" aria-hidden="true">
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
            <label for="imei_serial" class="block text-sm font-semibold text-gray-700 mb-3">
                2. {{ __('check.enter_imei') }}
            </label>
            <div class="relative">
                <i class="fas fa-barcode absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" aria-hidden="true"></i>
                <input type="text"
                    id="imei_serial"
                    name="imei_serial"
                    x-model="imei"
                    @input="normalise()"
                    maxlength="20"
                    spellcheck="false"
                    autocomplete="off"
                    autocapitalize="characters"
                    inputmode="text"
                    aria-describedby="imei-hint"
                    :aria-invalid="mismatchWarning ? 'true' : 'false'"
                    placeholder="{{ app()->getLocale()==='th' ? 'เช่น LKQD2YD439 หรือ 353xxxxxxxxxxxx' : 'e.g. LKQD2YD439 or 353xxxxxxxxxxxx' }}"
                    class="w-full pl-11 pr-4 py-3.5 rounded-xl border border-gray-200 bg-gray-50 font-mono text-base tracking-wide
                           focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 outline-none transition">
            </div>
            @error('imei_serial')<p class="mt-2 text-xs text-red-600" role="alert"><i class="fas fa-triangle-exclamation mr-1"></i>{{ $message }}</p>@enderror
            @if(session('error'))
                <p class="mt-2 text-xs text-red-600 flex items-start gap-1" role="alert">
                    <i class="fas fa-triangle-exclamation mt-0.5"></i>{{ session('error') }}
                </p>
            @endif
            <p x-show="mismatchWarning" x-transition
               class="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 flex items-start gap-2"
               role="alert">
                <i class="fas fa-triangle-exclamation mt-0.5"></i>
                <span x-text="mismatchWarning"></span>
            </p>
            <p id="imei-hint" class="mt-2.5 flex items-start gap-2 text-xs text-gray-500 leading-relaxed">
                <i class="fas fa-circle-info mt-0.5 text-gray-400" aria-hidden="true"></i>
                <span>{{ __('check.imei_hint') }}</span>
            </p>

            {{-- footer --}}
            <div class="flex items-center justify-between mt-6 pt-5 border-t border-gray-100">
                <p class="text-sm text-gray-500">
                    {{ app()->getLocale()==='th' ? 'ค่าบริการ' : 'Service fee' }}
                    <b class="text-gray-900 font-mono">฿<span x-text="price"></span></b>
                    · {{ app()->getLocale()==='th' ? 'ตัดจากเครดิตเมื่อตรวจสำเร็จ' : 'deducted on successful check' }}
                </p>
                <button type="submit"
                    :disabled="!valid"
                    :class="valid ? 'bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-600/25 cursor-pointer' : 'bg-gray-200 text-gray-400 cursor-not-allowed'"
                    class="inline-flex items-center gap-2 px-7 py-3 rounded-xl text-white text-sm font-semibold transition">
                    <template x-if="!submitting">
                        <span class="inline-flex items-center gap-2"><i class="fas fa-search"></i> {{ __('check.check_btn') }}</span>
                    </template>
                    <template x-if="submitting">
                        <span class="inline-flex items-center gap-2">
                            <i class="fas fa-spinner fa-spin"></i>
                            {{ app()->getLocale()==='th' ? 'กำลังตรวจสอบ...' : 'Checking...' }}
                        </span>
                    </template>
                </button>
            </div>
        </div>
    </form>

</div>
@endsection
