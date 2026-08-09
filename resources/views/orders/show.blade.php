@extends('layouts.app')
@section('title', __('order.result').' #'.$order->id)
@section('content')

@php
    $banner = [
        'clean'       => ['from'=>'from-green-500','to'=>'to-green-700','icon'=>'fa-shield-heart','shadow'=>'shadow-green-500/20'],
        'locked'      => ['from'=>'from-red-500','to'=>'to-red-700','icon'=>'fa-lock','shadow'=>'shadow-red-500/20'],
        'blacklisted' => ['from'=>'from-orange-500','to'=>'to-orange-700','icon'=>'fa-ban','shadow'=>'shadow-orange-500/20'],
        'unknown'     => ['from'=>'from-gray-500','to'=>'to-gray-700','icon'=>'fa-circle-question','shadow'=>'shadow-gray-500/20'],
    ][$overallStatus] ?? ['from'=>'from-gray-500','to'=>'to-gray-700','icon'=>'fa-circle-question','shadow'=>'shadow-gray-500/20'];

    // heuristic per-flag colouring
    $flagColor = function ($val, $badWords) {
        $v = strtolower((string) $val);
        if ($v === '' ) return ['gray','fa-minus'];
        foreach ($badWords as $w) if (str_contains($v, $w)) return ['red','fa-circle-xmark'];
        return ['green','fa-circle-check'];
    };
    $flags = [
        ['label'=>__('order.fmi_status'), 'val'=>$order->result_fmi,        'bad'=>['on','yes','enabled','active','lock']],
        ['label'=>__('order.activation'), 'val'=>$order->result_activation, 'bad'=>['lock','on']],
        ['label'=>__('order.mdm'),        'val'=>$order->result_mdm,        'bad'=>['enroll','on','yes','managed']],
        ['label'=>__('order.blacklist'),  'val'=>$order->result_blacklist,  'bad'=>['black','lost','stolen','block','yes']],
        ['label'=>__('order.simlock'),    'val'=>$order->result_simlock,    'bad'=>['lock','locked']],
    ];
    $tone = ['green'=>['bg-green-50','border-green-200','text-green-600'],'red'=>['bg-red-50','border-red-200','text-red-600'],'gray'=>['bg-gray-50','border-gray-200','text-gray-500']];
@endphp

<div class="max-w-3xl">

    <a href="{{ route('orders.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-blue-600 mb-5 transition">
        <i class="fas fa-arrow-left"></i> {{ __('app.order_history') }}
    </a>

    @if($order->status === 'error')
        <div class="bg-red-50 border border-red-200 rounded-2xl p-6 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-red-100 flex items-center justify-center shrink-0">
                <i class="fas fa-triangle-exclamation text-red-600 text-xl"></i>
            </div>
            <div>
                <p class="font-semibold text-red-800">{{ __('order.error') }}</p>
                <p class="text-sm text-red-600 mt-0.5">{{ $order->error_message ?? '—' }}</p>
            </div>
        </div>
    @else

    {{-- verdict banner --}}
    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 sm:gap-5 p-5 sm:p-6 rounded-2xl bg-gradient-to-br {{ $banner['from'] }} {{ $banner['to'] }} shadow-xl {{ $banner['shadow'] }}">
        <div class="w-12 h-12 sm:w-14 sm:h-14 rounded-2xl bg-white/20 flex items-center justify-center shrink-0">
            <i class="fas {{ $banner['icon'] }} text-white text-xl sm:text-2xl"></i>
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-lg sm:text-xl font-bold text-white">{{ __('order.'.$overallStatus) }}</p>
            <p class="text-sm text-white/85 mt-0.5 truncate">{{ $order->result_model ?? $order->imei_serial }}</p>
        </div>
        <div class="text-left sm:text-right text-white/80 text-xs">
            <p>Order #{{ $order->id }}</p>
            <p class="font-mono mt-0.5">{{ optional($order->processed_at)->format('d/m/y H:i') }}</p>
        </div>
    </div>

    {{-- status flags --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5 sm:gap-3 mt-4">
        @foreach($flags as $f)
            @php [$c,$ic] = $flagColor($f['val'], $f['bad']); $t = $tone[$c]; @endphp
            <div class="rounded-2xl border {{ $t[0] }} {{ $t[1] }} p-3 sm:p-4">
                <div class="flex items-center gap-2">
                    <i class="fas {{ $ic }} {{ $t[2] }} text-sm"></i>
                    <span class="text-xs font-semibold text-gray-500">{{ $f['label'] }}</span>
                </div>
                <p class="mt-2.5 text-sm sm:text-base font-bold {{ $t[2] }}">{{ $f['val'] ?: '—' }}</p>
            </div>
        @endforeach
    </div>

    {{-- device hero + grouped details --}}
    @php
        $pid = optional($order->service)->provider_service_id;
        $isLaptop = $order->service->device_type === 'macbook';
        $isTablet = $order->service->device_type === 'ipad';
    @endphp
    <div class="bg-white border border-gray-200 rounded-2xl mt-4 shadow-sm overflow-hidden">
        <div class="flex flex-col items-center gap-4 px-6 py-8 border-b border-gray-100">
            <div style="height:150px;display:flex;align-items:center;justify-content:center;">
                @if($isLaptop)
                <div style="width:200px;">
                    <div style="width:100%;height:108px;background:#1e293b;border-radius:9px 9px 3px 3px;padding:5px;box-sizing:border-box;">
                        <div style="width:100%;height:100%;border-radius:4px;background:linear-gradient(135deg,#2563eb,#7c3aed,#16a34a);"></div>
                    </div>
                    <div style="width:112%;margin-left:-6%;height:8px;background:linear-gradient(180deg,#cbd5e1,#94a3b8);border-radius:0 0 5px 5px;"></div>
                </div>
                @elseif($isTablet)
                <div style="width:100px;height:134px;background:#1e293b;border-radius:14px;padding:7px;box-sizing:border-box;">
                    <div style="width:100%;height:100%;border-radius:7px;background:linear-gradient(135deg,#2563eb,#7c3aed);"></div>
                </div>
                @else
                <div style="width:70px;height:142px;background:#1e293b;border-radius:17px;padding:5px;box-sizing:border-box;">
                    <div style="width:100%;height:100%;border-radius:12px;background:linear-gradient(160deg,#2563eb,#7c3aed);position:relative;">
                        <div style="position:absolute;top:7px;left:50%;transform:translateX(-50%);width:24px;height:5px;background:#0f172a;border-radius:99px;"></div>
                    </div>
                </div>
                @endif
            </div>
            <div class="text-center">
                <p class="text-lg font-bold text-gray-900">{{ $order->result_model ?? '—' }}</p>
                <p class="mt-0.5 text-sm text-gray-500">
                    {{ collect([$order->result_color, $order->result_storage, $order->result_region])->filter()->implode(' · ') ?: '—' }}
                </p>
                <div class="mt-2 flex flex-wrap justify-center gap-2">
                    @if($order->result_serial)<span class="text-[11px] font-mono bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full">SN: {{ $order->result_serial }}</span>@endif
                    <span class="text-[11px] font-mono bg-gray-100 text-gray-600 px-2.5 py-1 rounded-full">{{ $order->result_imei ? 'IMEI: '.$order->result_imei : $order->imei_serial }}</span>
                </div>
            </div>
        </div>

        @php
            $pillClass = [
                'green' => 'bg-green-500 text-white',
                'red'   => 'bg-red-500 text-white',
                'amber' => 'bg-amber-400 text-white',
                'gray'  => 'bg-gray-200 text-gray-500',
            ];
            $badWords = ['on','yes','lock','locked','enroll','managed','black','lost','stolen','block','expired','out'];
            $pillKind = function ($val) use ($badWords) {
                $v = strtolower(trim((string) $val));
                if ($v === '' || $v === 'n/a') return 'gray';
                foreach ($badWords as $w) if (str_contains($v, $w)) return 'red';
                return 'green';
            };
            $groups = [
                [
                    'title' => app()->getLocale()==='th' ? 'ข้อมูลเครื่อง' : 'Hardware',
                    'rows' => [
                        ['t', __('order.model'),         $order->result_model],
                        ['t', __('order.color'),         $order->result_color],
                        ['t', __('order.storage'),       $order->result_storage],
                        ['t', __('order.region'),        $order->result_region],
                        ['m', __('order.imei_serial'),   $order->result_serial ?? $order->result_imei ?? $order->imei_serial],
                        ['t', __('order.service'),       $order->service->name ?? null],
                    ],
                ],
                [
                    'title' => app()->getLocale()==='th' ? 'ประกัน & สถานะเครื่อง' : 'Warranty & Device Status',
                    'rows' => [
                        ['p', __('order.warranty'),      $order->result_warranty],
                        ['t', __('order.purchase_date'), $order->result_purchase_date],
                        ['p', __('order.fmi_status'),    $order->result_fmi],
                        ['p', __('order.activation'),    $order->result_activation],
                        ['p', __('order.mdm'),           $order->result_mdm],
                        ['p', __('order.blacklist'),     $order->result_blacklist],
                        ['p', __('order.simlock'),       $order->result_simlock],
                        ['p', __('order.replaced'),      $order->result_replaced],
                    ],
                ],
            ];
        @endphp

        @foreach($groups as $g)
        <p class="px-6 pt-5 pb-1 text-center text-[11px] font-bold uppercase tracking-wider text-gray-400">{{ $g['title'] }}</p>
        <div class="px-6 pb-4 max-w-md mx-auto">
            @foreach($g['rows'] as [$type, $label, $val])
            <div class="flex items-center justify-center gap-2 py-2.5 border-b border-gray-100 text-center">
                <span class="text-sm text-gray-500">{{ $label }}:</span>
                @if($type === 'p' && $val)
                    <span class="text-xs font-bold px-3 py-1 rounded-full whitespace-nowrap {{ $pillClass[$pillKind($val)] }}">{{ $val }}</span>
                @elseif($type === 'm')
                    <span class="text-sm font-bold font-mono text-gray-900">{{ $val ?: '—' }}</span>
                @else
                    <span class="text-sm font-bold text-gray-900">{{ $val ?: '—' }}</span>
                @endif
            </div>
            @endforeach
        </div>
        @endforeach
    </div>
    @endif

    {{-- actions --}}
    <div class="flex flex-col sm:flex-row gap-3 mt-6">
        <a href="{{ route('check.index') }}" class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-xl text-sm font-semibold transition">
            <i class="fas fa-search"></i> {{ __('check.check_btn') }}
        </a>
        <button onclick="window.print()" class="inline-flex items-center justify-center gap-2 bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-6 py-3 rounded-xl text-sm font-semibold transition">
            <i class="fas fa-download"></i> {{ app()->getLocale()==='th' ? 'บันทึกผล' : 'Save result' }}
        </button>
    </div>
</div>
@endsection
