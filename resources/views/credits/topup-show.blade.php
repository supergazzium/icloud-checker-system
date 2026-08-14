@extends('layouts.app')
@section('title', __('app.topup').' #'.$topup->id)
@section('content')
<div class="max-w-2xl mx-auto">
    <a href="{{ route('credits.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-flex items-center gap-2">
        <i class="fas fa-arrow-left"></i>{{ __('app.back_to_credits') }}
    </a>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-6 border-b">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">฿{{ number_format($topup->amount,2) }}</h2>
                    <p class="text-sm text-gray-500">{{ __('app.topup_ref') }} #{{ $topup->id }}</p>
                </div>
                @php
                    $badge = match($topup->status) {
                        'pending_review' => ['bg-yellow-100 text-yellow-800', 'app.pending_review', 'fa-hourglass-half'],
                        'approved', 'paid' => ['bg-green-100 text-green-800', 'app.approved', 'fa-circle-check'],
                        'rejected' => ['bg-red-100 text-red-800', 'app.rejected', 'fa-circle-xmark'],
                        default => ['bg-gray-100 text-gray-700', 'app.status_other', 'fa-circle-info'],
                    };
                @endphp
                <span class="px-4 py-2 rounded-full text-sm font-semibold {{ $badge[0] }}">
                    <i class="fas {{ $badge[2] }} mr-1"></i>{{ __($badge[1]) }}
                </span>
            </div>
        </div>

        <div class="p-6 space-y-4 text-sm">
            @if($topup->bankAccount)
            <div>
                <p class="text-xs text-gray-500 uppercase mb-1">{{ __('app.paid_to') }}</p>
                <p class="font-semibold text-gray-900">{{ $topup->bankAccount->bank_name }}</p>
                <div class="flex items-center gap-2" x-data="{ copied: false }">
                    <p class="font-mono text-gray-700">{{ $topup->bankAccount->account_number }}</p>
                    <button type="button"
                            @click="navigator.clipboard.writeText('{{ preg_replace('/\D+/', '', $topup->bankAccount->account_number) }}').then(() => { copied = true; setTimeout(() => copied = false, 1500) })"
                            :class="copied ? 'text-green-600' : 'text-gray-400 hover:text-blue-600'"
                            class="text-xs transition"
                            :title="copied ? '{{ __('app.copied') }}' : '{{ __('app.copy_account_number') }}'">
                        <i class="fas" :class="copied ? 'fa-check' : 'fa-copy'"></i>
                        <span x-show="copied" x-transition class="ml-1">{{ __('app.copied') }}</span>
                    </button>
                </div>
                <p class="text-gray-500">{{ $topup->bankAccount->account_name }}</p>
            </div>
            @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-1">{{ __('app.transfer_reference') }}</p>
                    <p class="font-mono text-gray-900">{{ $topup->transfer_reference }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-1">{{ __('app.transfer_date') }}</p>
                    <p class="text-gray-900">{{ optional($topup->transfer_date)->format('d/m/Y') ?? '-' }}</p>
                </div>
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-1">{{ __('app.submitted_at') }}</p>
                    <p class="text-gray-900">{{ $topup->created_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($topup->reviewed_at)
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-1">{{ __('app.reviewed_at') }}</p>
                    <p class="text-gray-900">{{ $topup->reviewed_at->format('d/m/Y H:i') }}</p>
                </div>
                @endif
            </div>

            @if($topup->isRejected() && $topup->rejection_reason)
            <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                <p class="text-xs font-semibold text-red-900 uppercase mb-1">{{ __('app.rejection_reason') }}</p>
                <p class="text-sm text-red-800">{{ $topup->rejection_reason }}</p>
            </div>
            @endif

            @if($topup->slip_path)
            <div>
                <p class="text-xs text-gray-500 uppercase mb-2">{{ __('app.uploaded_slip') }}</p>
                <a href="{{ route('credits.topup.slip', $topup) }}" target="_blank"
                   class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-800 text-sm font-semibold">
                    <i class="fas fa-file-image"></i>{{ __('app.view_slip') }}
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
