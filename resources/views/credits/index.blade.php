@extends('layouts.app')
@section('title',__('app.credits'))
@section('content')
<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ __('app.credits') }}</h2>
        <p class="text-gray-500 mt-1">{{ __('app.balance') }}: <span class="font-bold text-blue-600">฿{{ number_format(auth()->user()->balance,2) }}</span></p>
    </div>
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6" x-data="{ tab:'card', amount: null, custom: '', loading: false, error: '' }">
    <div class="flex gap-2 mb-5 border-b border-gray-100">
        <button type="button" @click="tab='card'" :class="tab==='card' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400'" class="px-1 pb-3 border-b-2 text-sm font-semibold"><i class="fas fa-credit-card mr-1.5"></i>บัตรเครดิต/เดบิต</button>
        <button type="button" @click="tab='qr'" :class="tab==='qr' ? 'border-blue-600 text-blue-600' : 'border-transparent text-gray-400'" class="px-1 pb-3 border-b-2 text-sm font-semibold ml-4"><i class="fas fa-qrcode mr-1.5"></i>พร้อมเพย์ (QR)</button>
    </div>

    @if($pendingTopup)
    <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-4 mb-4 flex items-center justify-between">
        <p class="text-sm text-yellow-800">มีรายการเติมเครดิต ฿{{ number_format($pendingTopup->amount,2) }} ที่ยังไม่เสร็จสมบูรณ์</p>
        <a href="{{ route('credits.topup.check',$pendingTopup) }}" class="text-sm font-semibold text-yellow-900 bg-yellow-200 hover:bg-yellow-300 px-3 py-1.5 rounded-lg">ตรวจสอบสถานะ</a>
    </div>
    @endif

    <div x-show="tab==='card'">
    <p class="text-sm text-gray-500 mb-2">เลือกจำนวนเงิน</p>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
        @foreach([300,500,1000,3000] as $preset)
        <button type="button" @click="amount={{ $preset }}; custom=''"
            :class="amount==={{ $preset }} ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:border-blue-300'"
            class="border rounded-xl py-3 font-bold transition">฿{{ number_format($preset) }}</button>
        @endforeach
    </div>
    <div class="flex items-center gap-3 mb-6">
        <span class="text-sm text-gray-500">หรือระบุจำนวนเอง</span>
        <input type="number" x-model="custom" @input="amount=null" min="100" max="100000" step="1"
               placeholder="เช่น 750" class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-40">
    </div>

    <p class="text-sm text-gray-500 mb-2">ข้อมูลบัตรเครดิต/เดบิต</p>
    <div class="grid sm:grid-cols-2 gap-3 mb-3">
        <input id="omise-card-number" type="text" inputmode="numeric" placeholder="หมายเลขบัตร" maxlength="19"
               class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm sm:col-span-2">
        <input id="omise-card-name" type="text" placeholder="ชื่อบนบัตร (ตัวพิมพ์ใหญ่)"
               class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm sm:col-span-2">
        <input id="omise-card-expiry" type="text" placeholder="MM/YY" maxlength="5"
               class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
        <input id="omise-card-cvv" type="text" inputmode="numeric" placeholder="CVV" maxlength="4"
               class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm">
    </div>

    <p x-show="error" x-text="error" class="text-sm text-red-600 mb-3"></p>

    <form id="omise-topup-form" method="POST" action="{{ route('credits.topup.store') }}">
        @csrf
        <input type="hidden" name="amount" :value="custom ? custom : amount">
        <input type="hidden" name="omise_token" id="omise-token">
        <button type="button" @click="submitTopup($event, {amount: custom ? custom : amount})"
            :disabled="(!amount && !custom) || loading"
            :class="((!amount && !custom) || loading) ? 'opacity-40 cursor-not-allowed' : 'hover:bg-blue-700'"
            class="bg-blue-600 text-white font-semibold px-6 py-3 rounded-xl transition">
            <i class="fas fa-lock mr-2"></i><span x-text="loading ? 'กำลังดำเนินการ...' : 'ยืนยันชำระเงิน'"></span>
        </button>
        <p class="text-xs text-gray-400 mt-3">ข้อมูลบัตรถูกเข้ารหัสส่งตรงไปยังผู้ให้บริการชำระเงิน (Omise/Opn Payments) ไม่ผ่านเซิร์ฟเวอร์ของเรา</p>
    </form>
    </div>

    <div x-show="tab==='qr'">
        <p class="text-sm text-gray-500 mb-2">เลือกจำนวนเงิน</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
            @foreach([300,500,1000,3000] as $preset)
            <button type="button" @click="amount={{ $preset }}; custom=''"
                :class="amount==={{ $preset }} ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-700 border-gray-200 hover:border-blue-300'"
                class="border rounded-xl py-3 font-bold transition">฿{{ number_format($preset) }}</button>
            @endforeach
        </div>
        <div class="flex items-center gap-3 mb-6">
            <span class="text-sm text-gray-500">หรือระบุจำนวนเอง</span>
            <input type="number" x-model="custom" @input="amount=null" min="20" max="100000" step="1"
                   placeholder="เช่น 750" class="border border-gray-200 rounded-lg px-3 py-2 text-sm w-40">
        </div>
        <form method="POST" action="{{ route('credits.topup.qr.store') }}">
            @csrf
            <input type="hidden" name="amount" :value="custom ? custom : amount">
            <button type="submit" :disabled="!amount && !custom"
                :class="(!amount && !custom) ? 'opacity-40 cursor-not-allowed' : 'hover:bg-blue-700'"
                class="bg-blue-600 text-white font-semibold px-6 py-3 rounded-xl transition">
                <i class="fas fa-qrcode mr-2"></i>สร้าง QR ชำระเงิน
            </button>
            <p class="text-xs text-gray-400 mt-3">สแกนจ่ายผ่านแอปธนาคารใดก็ได้ที่รองรับพร้อมเพย์ เครดิตจะเข้าอัตโนมัติหลังชำระเงินสำเร็จ</p>
        </form>
    </div>
</div>

@if(session('qrTopup'))
@php $qr = session('qrTopup'); @endphp
<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4" x-data="{ open: true }" x-show="open">
    <div class="bg-white rounded-2xl p-6 max-w-sm w-full text-center">
        <p class="font-semibold text-gray-900 mb-1">สแกนจ่ายผ่านพร้อมเพย์</p>
        <p class="text-sm text-gray-500 mb-4">ยอดชำระ ฿{{ number_format($qr['amount'],2) }}</p>
        <img src="{{ $qr['image'] }}" alt="PromptPay QR" class="w-56 h-56 mx-auto border border-gray-100 rounded-xl">
        <p id="qr-status-text" class="text-sm text-gray-500 mt-4">กำลังรอการชำระเงิน...</p>
        <button @click="open=false" class="mt-4 text-sm text-gray-400 hover:text-gray-600">ปิด</button>
    </div>
</div>
<script>
(function poll() {
    fetch('{{ route("credits.topup.qr.status", $qr["id"]) }}').then(r => r.json()).then(d => {
        if (d.status === 'paid') {
            document.getElementById('qr-status-text').textContent = 'ชำระเงินสำเร็จ! กำลังโหลดหน้าใหม่...';
            setTimeout(() => location.reload(), 1200);
        } else {
            setTimeout(poll, 3000);
        }
    }).catch(() => setTimeout(poll, 3000));
})();
</script>
@endif

<script src="https://cdn.omise.co/omise.js"></script>
<script>
Omise.setPublicKey('{{ config('omise.public_key') }}');
function submitTopup(evt, state) {
    const root = evt.target.closest('[x-data]');
    const setError = (msg) => { Alpine.$data(root).error = msg; Alpine.$data(root).loading = false; };
    if (!state.amount) return;
    Alpine.$data(root).error = '';
    Alpine.$data(root).loading = true;

    const number = document.getElementById('omise-card-number').value.replace(/\s+/g,'');
    const name   = document.getElementById('omise-card-name').value;
    const expiry = document.getElementById('omise-card-expiry').value.split('/');
    const cvv    = document.getElementById('omise-card-cvv').value;

    if (!number || !name || expiry.length !== 2 || !cvv) {
        setError('กรุณากรอกข้อมูลบัตรให้ครบถ้วน');
        return;
    }

    Omise.createToken('card', {
        name: name,
        number: number,
        expiration_month: expiry[0].trim(),
        expiration_year: '20' + expiry[1].trim(),
        security_code: cvv,
    }, function (statusCode, response) {
        if (statusCode !== 200) {
            setError(response.message || 'ข้อมูลบัตรไม่ถูกต้อง');
            return;
        }
        document.getElementById('omise-token').value = response.id;
        document.getElementById('omise-topup-form').submit();
    });
}
</script>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left py-4 px-6 text-xs font-semibold text-gray-500 uppercase">ประเภท</th>
                <th class="text-left py-4 px-4 text-xs font-semibold text-gray-500 uppercase">รายละเอียด</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500 uppercase">จำนวน</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500 uppercase">คงเหลือ</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500 uppercase">วันที่</th>
                <th class="text-right py-4 px-6 text-xs font-semibold text-gray-500 uppercase"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($transactions as $tx)
            <tr class="hover:bg-gray-50">
                <td class="py-4 px-6">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $tx->type==='topup' ? 'bg-green-100 text-green-800' : ($tx->type==='refund' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                        {{ $tx->type === 'topup' ? 'เติมเครดิต' : ($tx->type==='refund' ? 'คืนเครดิต' : 'ตัดเครดิต') }}
                    </span>
                </td>
                <td class="py-4 px-4 text-sm text-gray-600">{{ $tx->description ?? '-' }}</td>
                <td class="py-4 px-4 text-right font-bold {{ $tx->type==='deduct' ? 'text-red-600' : 'text-green-600' }}">
                    {{ $tx->type==='deduct' ? '-' : '+' }}฿{{ number_format($tx->amount,2) }}
                </td>
                <td class="py-4 px-4 text-right text-sm font-semibold text-gray-900">฿{{ number_format($tx->balance_after,2) }}</td>
                <td class="py-4 px-4 text-right text-xs text-gray-400">{{ $tx->created_at->format('d/m/y H:i') }}</td>
                <td class="py-4 px-6 text-right">
                    <a href="{{ route('credits.receipt',$tx) }}" class="text-blue-600 hover:text-blue-800 text-sm" title="ดาวน์โหลดใบเสร็จ"><i class="fas fa-file-pdf"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-16 text-gray-400">ยังไม่มีรายการ</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($transactions->hasPages())
    <div class="p-4 border-t">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection