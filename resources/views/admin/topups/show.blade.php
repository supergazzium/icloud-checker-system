@extends('layouts.app')
@section('title', __('app.topup').' #'.$topup->id)
@section('content')
<div class="max-w-4xl mx-auto">
    <a href="{{ route('admin.topups.index') }}" class="text-sm text-gray-500 hover:text-gray-700 mb-4 inline-flex items-center gap-2">
        <i class="fas fa-arrow-left"></i>{{ __('app.back_to_topups') }}
    </a>

    @if(session('success'))<div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-3 mb-4 text-sm">{{ session('success') }}</div>@endif

    <div class="grid lg:grid-cols-2 gap-6">
        {{-- Left: topup + slip --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-6 border-b">
                <div class="flex items-center justify-between">
                    <h2 class="text-2xl font-bold text-gray-900">฿{{ number_format($topup->amount,2) }}</h2>
                    @php
                        $badge = match($topup->status) {
                            'pending_review' => 'bg-yellow-100 text-yellow-800',
                            'approved', 'paid' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <span class="text-sm font-semibold px-3 py-1 rounded-full {{ $badge }}">{{ $topup->status }}</span>
                </div>
                <p class="text-sm text-gray-500 mt-1">#{{ $topup->id }} — {{ $topup->created_at->format('d/m/Y H:i') }}</p>
            </div>
            <div class="p-6 space-y-4 text-sm">
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-1">{{ __('app.user') }}</p>
                    <p class="font-semibold text-gray-900">{{ $topup->user->name }} ({{ $topup->user->email }})</p>
                    <p class="text-xs text-gray-500">{{ __('app.current_balance') }}: ฿{{ number_format($topup->user->balance,2) }}</p>
                </div>
                @if($topup->bankAccount)
                <div>
                    <p class="text-xs text-gray-500 uppercase mb-1">{{ __('app.paid_to') }}</p>
                    <p class="font-semibold text-gray-900">{{ $topup->bankAccount->bank_name }}</p>
                    <p class="font-mono text-gray-700">{{ $topup->bankAccount->account_number }}</p>
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
                </div>
                @if($topup->reviewer)
                <div class="text-xs text-gray-500 border-t pt-3">
                    <i class="fas fa-user-shield mr-1"></i>{{ __('app.reviewed_by') }}: {{ $topup->reviewer->name }}
                    ({{ $topup->reviewed_at->format('d/m/Y H:i') }})
                </div>
                @endif
                @if($topup->isRejected() && $topup->rejection_reason)
                <div class="bg-red-50 border border-red-200 rounded-xl p-3 text-sm text-red-800">
                    <p class="font-semibold mb-1">{{ __('app.rejection_reason') }}</p>
                    {{ $topup->rejection_reason }}
                </div>
                @endif
            </div>
        </div>

        {{-- Right: slip preview + actions --}}
        <div class="space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <h3 class="font-semibold text-gray-900 mb-3">{{ __('app.uploaded_slip') }}</h3>
                @if($topup->slip_path)
                    @php $ext = strtolower(pathinfo($topup->slip_path, PATHINFO_EXTENSION)); @endphp
                    @if(in_array($ext, ['jpg','jpeg','png','webp']))
                        <a href="{{ route('credits.topup.slip', $topup) }}" target="_blank">
                            <img src="{{ route('credits.topup.slip', $topup) }}" alt="slip" class="w-full rounded-xl border border-gray-100">
                        </a>
                    @else
                        <a href="{{ route('credits.topup.slip', $topup) }}" target="_blank"
                           class="flex items-center gap-3 border border-gray-200 rounded-xl p-4 hover:border-blue-400">
                            <i class="fas fa-file-pdf text-red-500 text-3xl"></i>
                            <div>
                                <p class="font-semibold text-gray-900">{{ __('app.view_pdf_slip') }}</p>
                                <p class="text-xs text-gray-500">{{ basename($topup->slip_path) }}</p>
                            </div>
                        </a>
                    @endif
                @else
                    <p class="text-sm text-gray-400">{{ __('app.no_slip') }}</p>
                @endif
            </div>

            @if($topup->isPendingReview())
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                <h3 class="font-semibold text-gray-900">{{ __('app.decision') }}</h3>
                <form method="POST" action="{{ route('admin.topups.approve', $topup) }}"
                      onsubmit="return confirm('{{ __('app.confirm_approve') }} ฿{{ number_format($topup->amount,2) }}?')">
                    @csrf
                    <button class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 rounded-xl">
                        <i class="fas fa-circle-check mr-2"></i>{{ __('app.approve_and_credit') }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.topups.reject', $topup) }}" class="space-y-2">
                    @csrf
                    <textarea name="rejection_reason" required maxlength="500" rows="2"
                              placeholder="{{ __('app.rejection_reason_placeholder') }}"
                              class="w-full border rounded-xl px-4 py-2.5 text-sm"></textarea>
                    @error('rejection_reason')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                    <button class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 rounded-xl">
                        <i class="fas fa-circle-xmark mr-2"></i>{{ __('app.reject') }}
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
