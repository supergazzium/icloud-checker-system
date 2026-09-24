@php
    // Icon + accent palette per device_type, so pricing cards keyed off the
    // live Service catalogue still get sensible iconography. Falls back to the
    // generic "all" bucket for anything unexpected.
    $deviceMeta = [
        'iphone'  => ['icon' => 'fa-mobile-screen',        'fg' => '#2563eb', 'bg' => '#eff6ff'],
        'ipad'    => ['icon' => 'fa-tablet-screen-button', 'fg' => '#d97706', 'bg' => '#fffbeb'],
        'macbook' => ['icon' => 'fa-laptop',               'fg' => '#16a34a', 'bg' => '#f0fdf4'],
        'all'     => ['icon' => 'fa-shield-halved',        'fg' => '#7c3aed', 'bg' => '#f5f3ff'],
    ];
    // The "recommended" ribbon goes on the single most expensive active
    // service (the Ultimate tier), computed once here rather than hardcoded.
    $featuredPrice = $services->max('sell_price');
    $featuredId    = optional($services->firstWhere('sell_price', $featuredPrice))->getKey();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'iPart Store') }} · เช็ก iCloud, MDM, Blacklist ก่อนซื้อ-ขาย</title>
    <meta name="description" content="กรอก IMEI หรือ Serial Number ของ iPhone, iPad, MacBook แล้วรู้สถานะ iCloud, MDM, Blacklist และประกันทันที">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('images/icon-192.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans:wght@400;500;600;700&family=Noto+Sans+Thai+Looped:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        [x-cloak] { display: none !important; }
        a { text-decoration: none; }
        @keyframes floatUp { from { opacity: 0; transform: translateY(14px); } to { opacity: 1; transform: none; } }
        .float-up { animation: floatUp .5s ease both; }
        .float-up-slow { animation: floatUp .6s ease .1s both; }
    </style>
</head>
<body class="font-sans text-slate-900 antialiased" style="background:#f8fafc;overflow-x:hidden;">

    {{-- NAV --}}
    <header x-data="{ open: false }" class="sticky top-0 z-40 border-b border-[#e8eef5]" style="background:rgba(248,250,252,.88);backdrop-filter:blur(12px);">
        <div class="max-w-[1120px] mx-auto px-6 py-3.5 flex items-center justify-between gap-4">
            <a href="{{ route('landing') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo.jpg') }}" alt="{{ config('app.name', 'iPart Store') }}" class="w-9 h-9 rounded-[9px] object-cover">
                <span class="font-bold text-base tracking-tight">{{ config('app.name', 'iPart Store') }}</span>
            </a>

            {{-- Desktop actions --}}
            <nav class="hidden sm:flex items-center gap-2">
                <a href="{{ url('/lang/' . (app()->getLocale() === 'th' ? 'en' : 'th')) }}"
                   class="px-3 py-2 rounded-[10px] text-sm font-semibold text-slate-500 hover:text-slate-700">
                    {{ app()->getLocale() === 'th' ? 'EN' : 'TH' }}
                </a>
                <a href="{{ route('login') }}" class="px-4 py-2.5 rounded-[10px] text-sm font-semibold text-slate-700 hover:text-slate-900">เข้าสู่ระบบ</a>
                <a href="{{ route('register') }}" class="px-[18px] py-2.5 rounded-[10px] text-sm font-semibold text-white" style="background:#2563eb;box-shadow:0 4px 14px rgba(37,99,235,.25);">สมัครใช้งาน</a>
            </nav>

            {{-- Mobile toggle --}}
            <button type="button" @click="open = !open" class="sm:hidden w-10 h-10 grid place-items-center rounded-[10px] text-slate-700" aria-label="Menu">
                <i class="fas" :class="open ? 'fa-xmark' : 'fa-bars'"></i>
            </button>
        </div>

        {{-- Mobile menu --}}
        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-150"
             x-transition:enter-start="opacity-0 -translate-y-1"
             x-transition:enter-end="opacity-100 translate-y-0"
             class="sm:hidden border-t border-[#e8eef5] bg-white">
            <div class="max-w-[1120px] mx-auto px-6 py-3 flex flex-col gap-1">
                <a href="{{ route('login') }}" class="px-2 py-2.5 rounded-lg text-sm font-semibold text-slate-700 hover:bg-slate-50">เข้าสู่ระบบ</a>
                <a href="{{ route('register') }}" class="px-2 py-2.5 rounded-lg text-sm font-semibold text-white text-center" style="background:#2563eb;">สมัครใช้งาน</a>
                <a href="{{ url('/lang/' . (app()->getLocale() === 'th' ? 'en' : 'th')) }}" class="px-2 py-2.5 rounded-lg text-sm font-semibold text-slate-500 hover:bg-slate-50">
                    {{ app()->getLocale() === 'th' ? 'English' : 'ภาษาไทย' }}
                </a>
            </div>
        </div>
    </header>

    {{-- HERO --}}
    <section class="max-w-[1120px] mx-auto px-6 pt-16 pb-14 grid gap-12 items-center" style="grid-template-columns:repeat(auto-fit,minmax(300px,1fr));">
        <div class="float-up">
            <div class="inline-flex items-center gap-2 px-3 py-1.5 rounded-full mb-5 text-xs font-semibold" style="background:#eff6ff;border:1px solid #dbeafe;color:#1d4ed8;">
                <i class="fas fa-bolt text-[10px]"></i> ผลตรวจภายใน 10 วินาที
            </div>
            <h1 class="m-0 font-bold leading-[1.15] tracking-tight" style="font-size:clamp(30px,4.4vw,46px);text-wrap:pretty;">
                เช็ก iCloud, MDM<br>และ Blacklist<br><span style="color:#2563eb;">ก่อนซื้อ-ขายทุกครั้ง</span>
            </h1>
            <p class="mt-[18px] text-base leading-[1.65] text-slate-500 max-w-[440px]" style="text-wrap:pretty;">
                กรอก IMEI หรือ Serial Number ของ iPhone, iPad, MacBook แล้วรู้สถานะเครื่องทันที ดึงข้อมูลตรงจากฐานข้อมูลผู้ผลิต ไม่ต้องรอ ไม่ต้องเสี่ยง
            </p>
            <div class="flex flex-wrap gap-3 mt-[30px]">
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2.5 px-[26px] py-3.5 rounded-[13px] text-white text-[15px] font-semibold" style="background:#2563eb;box-shadow:0 8px 24px rgba(37,99,235,.3);">
                    <i class="fas fa-magnifying-glass text-[13px]"></i> เริ่มตรวจสอบเลย
                </a>
                <a href="#pricing" class="inline-flex items-center gap-2.5 px-6 py-3.5 rounded-[13px] bg-white text-slate-700 text-[15px] font-semibold" style="border:1.5px solid #e2e8f0;">
                    ดูราคาบริการ
                </a>
            </div>
            <div class="flex flex-wrap gap-[22px] mt-8">
                <div>
                    <div class="text-[22px] font-bold">10 วิ</div>
                    <div class="text-xs text-slate-500 mt-px">เวลาตรวจเฉลี่ย</div>
                </div>
                <div class="w-px bg-slate-200"></div>
                <div>
                    <div class="text-[22px] font-bold">{{ $services->count() ?: 4 }} บริการ</div>
                    <div class="text-xs text-slate-500 mt-px">แพ็กเกจที่เปิดให้ตรวจ</div>
                </div>
                <div class="w-px bg-slate-200"></div>
                <div>
                    <div class="text-[22px] font-bold">24 ชม.</div>
                    <div class="text-xs text-slate-500 mt-px">ใช้งานได้ตลอด</div>
                </div>
            </div>
        </div>

        {{-- result preview card --}}
        <div class="float-up-slow">
            <div class="bg-white rounded-[22px] overflow-hidden" style="border:1px solid #e8eef5;box-shadow:0 20px 50px rgba(15,23,42,.09);">
                <div class="flex items-center gap-3.5 p-5" style="background:linear-gradient(135deg,#16a34a,#15803d);">
                    <div class="w-11 h-11 rounded-[13px] grid place-items-center" style="background:rgba(255,255,255,.2);">
                        <i class="fas fa-shield-halved text-white text-lg"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-base font-bold text-white">อุปกรณ์สะอาด ปลอดภัย</div>
                        <div class="text-xs mt-0.5" style="color:rgba(255,255,255,.85);">iPhone 15 Pro Max</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-2.5 p-[18px]">
                    @foreach ([['Find My','OFF','fa-circle-check'],['MDM Lock','Clean','fa-circle-check'],['Blacklist','Clean','fa-circle-check']] as [$label, $val, $icon])
                        <div class="rounded-[14px] p-3" style="border:1px solid #dcfce7;background:#f0fdf4;">
                            <div class="flex items-center gap-1.5">
                                <i class="fas {{ $icon }} text-[11px]" style="color:#16a34a;"></i>
                                <span class="text-[11px] font-semibold text-slate-500">{{ $label }}</span>
                            </div>
                            <div class="mt-[7px] text-sm font-bold" style="color:#15803d;">{{ $val }}</div>
                        </div>
                    @endforeach
                    <div class="rounded-[14px] p-3" style="border:1px solid #e2e8f0;background:#f8fafc;">
                        <div class="flex items-center gap-1.5">
                            <i class="fas fa-sim-card text-[11px] text-slate-500"></i>
                            <span class="text-[11px] font-semibold text-slate-500">SIM Lock</span>
                        </div>
                        <div class="mt-[7px] text-sm font-bold text-slate-700">Unlocked</div>
                    </div>
                </div>

                <div class="px-[18px] pb-5">
                    <div class="flex justify-between py-[9px] border-b border-slate-100">
                        <span class="text-[13px] text-slate-500">ประกัน</span>
                        <span class="text-[13px] font-bold">Active · 2027-01-14</span>
                    </div>
                    <div class="flex justify-between py-[9px] border-b border-slate-100">
                        <span class="text-[13px] text-slate-500">ความจุ</span>
                        <span class="text-[13px] font-bold">512 GB</span>
                    </div>
                    <div class="flex justify-between py-[9px]">
                        <span class="text-[13px] text-slate-500">Serial</span>
                        <span class="text-[13px] font-bold" style="font-family:ui-monospace,monospace;">F2LW3XK9NP7Q</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- WHAT YOU GET --}}
    <section class="bg-white" style="border-top:1px solid #e8eef5;border-bottom:1px solid #e8eef5;">
        <div class="max-w-[1120px] mx-auto px-6 py-[60px]">
            <h2 class="m-0 mb-2 text-[26px] font-bold tracking-tight">ผลตรวจบอกอะไรคุณ</h2>
            <p class="m-0 mb-9 text-[15px] text-slate-500">ทุกจุดที่ต้องรู้ก่อนตัดสินใจ ในรายงานเดียว</p>

            <div class="grid gap-4" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr));">
                @foreach ([
                    ['fa-cloud','#2563eb','#eff6ff','iCloud / Find My','รู้ว่าเครื่องยังผูกบัญชี Apple ID เดิมอยู่หรือไม่ ก่อนจ่ายเงิน'],
                    ['fa-building-lock','#7c3aed','#f5f3ff','MDM Lock','เครื่ององค์กรที่ถูกควบคุมระยะไกล ใช้งานส่วนตัวไม่ได้เต็มที่'],
                    ['fa-ban','#dc2626','#fef2f2','Blacklist / Lost','เครื่องแจ้งหายหรือถูกบล็อกจากเครือข่าย ใช้กับซิมไม่ได้'],
                    ['fa-file-shield','#16a34a','#f0fdf4','ประกัน & รุ่นเครื่อง','รุ่นจริง ความจุ สี ประเทศต้นทาง และสถานะประกันคงเหลือ'],
                ] as [$icon, $fg, $bg, $title, $desc])
                    <div class="rounded-2xl p-[22px]" style="background:#f8fafc;border:1px solid #e8eef5;">
                        <div class="w-10 h-10 rounded-[11px] grid place-items-center mb-3.5" style="background:{{ $bg }};">
                            <i class="fas {{ $icon }} text-base" style="color:{{ $fg }};"></i>
                        </div>
                        <div class="text-[15px] font-bold mb-1.5">{{ $title }}</div>
                        <div class="text-[13px] leading-[1.6] text-slate-500">{{ $desc }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- HOW IT WORKS --}}
    <section class="max-w-[1120px] mx-auto px-6 py-[60px]">
        <h2 class="m-0 mb-9 text-[26px] font-bold tracking-tight">ใช้งานง่าย 3 ขั้นตอน</h2>
        <div class="grid gap-5" style="grid-template-columns:repeat(auto-fit,minmax(250px,1fr));">
            @foreach ([
                ['1','เติมเครดิต','จ่ายผ่านบัตรเครดิตหรือสแกน QR พร้อมเพย์ เครดิตเข้าบัญชีทันที'],
                ['2','เลือกบริการ + กรอก IMEI','เลือกประเภทอุปกรณ์ที่ต้องการตรวจ วาง IMEI หรือ Serial Number ลงไป'],
                ['3','รับผลทันที','อ่านรายงานได้เลย บันทึกเป็น PDF หรือย้อนดูประวัติได้ตลอด'],
            ] as [$n, $title, $desc])
                <div>
                    <div class="flex items-center gap-2.5 mb-3">
                        <div class="w-[30px] h-[30px] rounded-[9px] text-white grid place-items-center text-sm font-bold" style="background:#2563eb;">{{ $n }}</div>
                        <div class="text-base font-bold">{{ $title }}</div>
                    </div>
                    <p class="m-0 text-sm leading-[1.65] text-slate-500">{{ $desc }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- PRICING --}}
    <section id="pricing" class="bg-white" style="border-top:1px solid #e8eef5;">
        <div class="max-w-[1120px] mx-auto px-6 py-[60px]">
            <h2 class="m-0 mb-2 text-[26px] font-bold tracking-tight">บริการและราคา</h2>
            <p class="m-0 mb-9 text-[15px] text-slate-500">จ่ายตามจำนวนที่ตรวจ ไม่มีค่าสมาชิกรายเดือน</p>

            @if ($services->isEmpty())
                <div class="rounded-2xl p-8 text-center text-slate-500 text-sm" style="background:#f8fafc;border:1px solid #e8eef5;">
                    ราคาบริการจะแสดงหลังเข้าสู่ระบบ — <a href="{{ route('register') }}" style="color:#2563eb;" class="font-semibold">สมัครใช้งานฟรี</a>
                </div>
            @else
                <div class="grid gap-4" style="grid-template-columns:repeat(auto-fit,minmax(230px,1fr));">
                    @foreach ($services as $service)
                        @php
                            $meta      = $deviceMeta[$service->device_type] ?? $deviceMeta['all'];
                            $isFeature = $featuredId !== null && $service->getKey() === $featuredId;
                        @endphp
                        <div class="rounded-[18px] p-6 relative" style="border:1.5px solid {{ $isFeature ? '#2563eb' : '#e8eef5' }};{{ $isFeature ? 'box-shadow:0 12px 32px rgba(37,99,235,.12);' : '' }}">
                            @if ($isFeature)
                                <div class="absolute left-6 text-white text-[11px] font-bold px-[11px] py-1 rounded-full" style="top:-11px;background:#2563eb;">แนะนำ</div>
                            @endif
                            <div class="w-[38px] h-[38px] rounded-[11px] grid place-items-center mb-3.5" style="background:{{ $meta['bg'] }};">
                                <i class="fas {{ $meta['icon'] }} text-[15px]" style="color:{{ $meta['fg'] }};"></i>
                            </div>
                            <div class="text-[15px] font-bold">{{ $service->name }}</div>
                            @if ($service->description)
                                <div class="text-[13px] text-slate-500 mt-[5px] leading-[1.55]">{{ $service->description }}</div>
                            @endif
                            <div class="mt-4 text-2xl font-bold">
                                ฿{{ rtrim(rtrim(number_format((float) $service->sell_price, 2), '0'), '.') }}<span class="text-[13px] text-slate-500 font-medium"> / ครั้ง</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="mt-[22px] m-0 text-xs text-slate-500">ราคาอาจเปลี่ยนแปลงได้ ดูราคาปัจจุบันหลังเข้าสู่ระบบ</p>
        </div>
    </section>

    {{-- TRUST --}}
    <section class="max-w-[1120px] mx-auto px-6 py-14">
        <div class="grid gap-7" style="grid-template-columns:repeat(auto-fit,minmax(220px,1fr));">
            @foreach ([
                ['fa-lock','ชำระเงินปลอดภัย','ข้อมูลบัตรเข้ารหัสส่งตรงถึงผู้ให้บริการ ไม่เก็บไว้บนเซิร์ฟเวอร์เรา'],
                ['fa-rotate-left','คืนเครดิตอัตโนมัติ','ถ้าระบบตรวจไม่สำเร็จ เครดิตคืนเข้าบัญชีให้ทันทีโดยไม่ต้องแจ้ง'],
                ['fa-mobile-screen-button','ใช้ได้ทุกอุปกรณ์','เปิดบนมือถือ iPad หรือคอมพิวเตอร์ ติดตั้งลงหน้าจอโฮมได้เลย'],
            ] as [$icon, $title, $desc])
                <div class="flex gap-3.5">
                    <i class="fas {{ $icon }} text-[17px] mt-0.5" style="color:#2563eb;"></i>
                    <div>
                        <div class="text-sm font-bold mb-1">{{ $title }}</div>
                        <div class="text-[13px] leading-[1.6] text-slate-500">{{ $desc }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- CTA --}}
    <section class="max-w-[1120px] mx-auto px-6 pb-16">
        <div class="rounded-3xl px-8 py-12 text-center" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);box-shadow:0 20px 50px rgba(37,99,235,.25);">
            <h2 class="m-0 font-bold text-white tracking-tight" style="font-size:clamp(22px,3vw,30px);">พร้อมเช็กเครื่องแรกของคุณแล้วหรือยัง</h2>
            <p class="mt-3 mx-auto text-[15px] leading-[1.6] max-w-[440px]" style="color:rgba(255,255,255,.9);">สมัครฟรี ใช้เวลาไม่ถึงนาที เติมเครดิตเท่าที่ใช้</p>
            <a href="{{ route('register') }}" class="inline-flex items-center gap-2.5 mt-[26px] px-[30px] py-3.5 rounded-[13px] bg-white text-[15px] font-bold" style="color:#1d4ed8;">
                สมัครใช้งานฟรี <i class="fas fa-arrow-right text-[13px]"></i>
            </a>
        </div>
    </section>

    {{-- FOOTER --}}
    <footer class="bg-white" style="border-top:1px solid #e8eef5;">
        <div class="max-w-[1120px] mx-auto px-6 py-8 flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo.jpg') }}" alt="{{ config('app.name', 'iPart Store') }}" class="w-7 h-7 rounded-lg object-cover">
                <span class="text-[13px] text-slate-500">© {{ date('Y') }} {{ config('app.name', 'iPart Store') }}</span>
            </div>
            <div class="flex flex-wrap gap-5 text-[13px]">
                <a href="{{ route('legal.terms') }}" style="color:#2563eb;">ข้อตกลงการใช้บริการ</a>
                <a href="{{ route('legal.privacy') }}" style="color:#2563eb;">นโยบายความเป็นส่วนตัว</a>
                <a href="{{ route('login') }}" style="color:#2563eb;">เข้าสู่ระบบ</a>
            </div>
        </div>
        <div class="max-w-[1120px] mx-auto px-6 pb-7">
            <p class="m-0 text-[11px] leading-[1.6] text-slate-500">{{ config('app.name', 'iPart Store') }} ไม่ใช่ตัวแทนหรือพันธมิตรอย่างเป็นทางการของ Apple Inc. ผลการตรวจสอบเป็นข้อมูลประกอบการพิจารณาเท่านั้น</p>
        </div>
    </footer>

</body>
</html>
