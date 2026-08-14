@extends('layouts.app')
@section('title',__('app.credits'))
@section('content')
<div class="flex items-center justify-between mb-8">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ __('app.credits') }}</h2>
        <p class="text-gray-500 mt-1">{{ __('app.balance') }}: <span class="font-bold text-blue-600">฿{{ number_format(auth()->user()->balance,2) }}</span></p>
    </div>
</div>

@if($pendingTopups->isNotEmpty())
<div class="bg-yellow-50 border border-yellow-200 rounded-2xl p-5 mb-6">
    <p class="font-semibold text-yellow-900 mb-3"><i class="fas fa-hourglass-half mr-2"></i>{{ __('app.pending_topups') }}</p>
    <div class="space-y-2">
        @foreach($pendingTopups as $pt)
        <a href="{{ route('credits.topup.show', $pt) }}" class="flex items-center justify-between bg-white border border-yellow-200 rounded-xl px-4 py-3 hover:border-yellow-400 transition">
            <div>
                <p class="text-sm font-semibold text-gray-900">฿{{ number_format($pt->amount,2) }} — {{ $pt->transfer_reference }}</p>
                <p class="text-xs text-gray-500">{{ __('app.submitted_at') }}: {{ $pt->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <span class="text-xs font-semibold text-yellow-800 bg-yellow-100 px-3 py-1 rounded-full">{{ __('app.pending_review') }}</span>
        </a>
        @endforeach
    </div>
</div>
@endif

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
    <h3 class="text-lg font-bold text-gray-900 mb-1">{{ __('app.topup') }}</h3>
    <p class="text-sm text-gray-500 mb-6">{{ __('app.topup_flow_intro') }}</p>

    @if($bankAccounts->isEmpty())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-sm text-red-800">
            <i class="fas fa-triangle-exclamation mr-2"></i>{{ __('app.no_bank_accounts') }}
        </div>
    @else
    <form method="POST" action="{{ route('credits.topup.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">1. {{ __('app.select_bank_account') }}</label>
            <div class="grid gap-3">
                @foreach($bankAccounts as $ba)
                <label class="flex items-start gap-3 border border-gray-200 rounded-xl p-4 cursor-pointer hover:border-blue-400 has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 transition">
                    <input type="radio" name="bank_account_id" value="{{ $ba->id }}" required class="mt-1">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-gray-900">{{ $ba->bank_name }}</p>
                        <div class="flex items-center gap-2" x-data="{ copied: false }">
                            <p class="text-sm text-gray-700 font-mono">{{ $ba->account_number }}</p>
                            <button type="button"
                                    @click.prevent.stop="navigator.clipboard.writeText('{{ preg_replace('/\D+/', '', $ba->account_number) }}').then(() => { copied = true; setTimeout(() => copied = false, 1500) })"
                                    :class="copied ? 'text-green-600' : 'text-gray-400 hover:text-blue-600'"
                                    class="text-xs transition"
                                    :title="copied ? '{{ __('app.copied') }}' : '{{ __('app.copy_account_number') }}'"
                                    :aria-label="copied ? '{{ __('app.copied') }}' : '{{ __('app.copy_account_number') }}'">
                                <i class="fas" :class="copied ? 'fa-check' : 'fa-copy'"></i>
                                <span x-show="copied" x-transition class="ml-1">{{ __('app.copied') }}</span>
                            </button>
                        </div>
                        <p class="text-sm text-gray-500">{{ $ba->account_name }}@if($ba->branch) &middot; {{ $ba->branch }}@endif</p>
                        @if($ba->notes)<p class="text-xs text-gray-400 mt-1">{{ $ba->notes }}</p>@endif
                    </div>
                </label>
                @endforeach
            </div>
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">2. {{ __('app.amount') }}</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">฿</span>
                    <input type="number" name="amount" min="1" max="1000000" step="0.01" required value="{{ old('amount') }}"
                           class="w-full pl-8 pr-3 py-2.5 border border-gray-200 rounded-xl text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">3. {{ __('app.transfer_date') }}</label>
                <input type="date" name="transfer_date" required value="{{ old('transfer_date', now()->toDateString()) }}"
                       max="{{ now()->toDateString() }}"
                       class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">4. {{ __('app.transfer_reference') }} <span class="text-red-500">*</span></label>
            <input type="text" name="transfer_reference" maxlength="100" required value="{{ old('transfer_reference') }}"
                   placeholder="{{ __('app.transfer_reference_placeholder') }}"
                   class="w-full px-3 py-2.5 border border-gray-200 rounded-xl text-sm font-mono">
            <p class="text-xs text-gray-500 mt-1">{{ __('app.transfer_reference_hint') }}</p>
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700 mb-2">5. {{ __('app.upload_slip') }} <span class="text-red-500">*</span></label>
            <input type="file" name="slip" accept="image/jpeg,image/png,image/webp,application/pdf" required
                   class="w-full text-sm border border-gray-200 rounded-xl px-3 py-2.5 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-blue-100 file:text-blue-700 file:font-semibold">
            <p class="text-xs text-gray-500 mt-1">{{ __('app.slip_hint') }}</p>
        </div>

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-800">
            @foreach($errors->all() as $err)<p><i class="fas fa-circle-exclamation mr-1"></i>{{ $err }}</p>@endforeach
        </div>
        @endif

        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-xl transition">
            <i class="fas fa-paper-plane mr-2"></i>{{ __('app.submit_topup') }}
        </button>
        <p class="text-xs text-gray-400 text-center">{{ __('app.approval_time_note') }}</p>
    </form>
    @endif
</div>

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b">
            <tr>
                <th class="text-left py-4 px-6 text-xs font-semibold text-gray-500 uppercase">{{ __('app.type') }}</th>
                <th class="text-left py-4 px-4 text-xs font-semibold text-gray-500 uppercase">{{ __('app.description') }}</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500 uppercase">{{ __('app.amount') }}</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500 uppercase">{{ __('app.balance_short') }}</th>
                <th class="text-right py-4 px-4 text-xs font-semibold text-gray-500 uppercase">{{ __('app.date') }}</th>
                <th class="text-right py-4 px-6 text-xs font-semibold text-gray-500 uppercase"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($transactions as $tx)
            <tr class="hover:bg-gray-50">
                <td class="py-4 px-6">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold {{ $tx->type==='topup' ? 'bg-green-100 text-green-800' : ($tx->type==='refund' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800') }}">
                        {{ $tx->type === 'topup' ? __('app.tx_topup') : ($tx->type==='refund' ? __('app.tx_refund') : __('app.tx_deduct')) }}
                    </span>
                </td>
                <td class="py-4 px-4 text-sm text-gray-600">{{ $tx->description ?? '-' }}</td>
                <td class="py-4 px-4 text-right font-bold {{ $tx->type==='deduct' ? 'text-red-600' : 'text-green-600' }}">
                    {{ $tx->type==='deduct' ? '-' : '+' }}฿{{ number_format($tx->amount,2) }}
                </td>
                <td class="py-4 px-4 text-right text-sm font-semibold text-gray-900">฿{{ number_format($tx->balance_after,2) }}</td>
                <td class="py-4 px-4 text-right text-xs text-gray-400">{{ $tx->created_at->format('d/m/y H:i') }}</td>
                <td class="py-4 px-6 text-right">
                    <a href="{{ route('credits.receipt',$tx) }}" class="text-blue-600 hover:text-blue-800 text-sm" title="{{ __('app.download_receipt') }}"><i class="fas fa-file-pdf"></i></a>
                </td>
            </tr>
            @empty
            <tr><td colspan="6" class="text-center py-16 text-gray-400">{{ __('app.no_transactions') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($transactions->hasPages())
    <div class="p-4 border-t">{{ $transactions->links() }}</div>
    @endif
</div>
@endsection
