@extends('layouts.app')
@section('title', __('app.topup_review'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ __('app.topup_review') }}</h2>
        <p class="text-gray-500 mt-1 text-sm">
            {{ __('app.pending_count') }}:
            <span class="font-bold text-yellow-700">{{ $pendingCount }}</span>
        </p>
    </div>
</div>

<div class="flex gap-2 mb-4 text-sm">
    @foreach(['pending_review' => __('app.pending_review'), 'approved' => __('app.approved'), 'rejected' => __('app.rejected'), 'all' => __('app.all')] as $key => $label)
    <a href="{{ route('admin.topups.index', ['status' => $key]) }}"
       class="px-4 py-2 rounded-xl font-semibold {{ $status === $key ? 'bg-blue-600 text-white' : 'bg-white text-gray-600 border border-gray-200 hover:border-blue-400' }}">
        {{ $label }}
    </a>
    @endforeach
</div>

@if(session('success'))<div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-3 mb-4 text-sm">{{ session('success') }}</div>@endif

<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b text-xs text-gray-500 uppercase">
            <tr>
                <th class="text-left py-3 px-5">#</th>
                <th class="text-left py-3 px-4">{{ __('app.user') }}</th>
                <th class="text-right py-3 px-4">{{ __('app.amount') }}</th>
                <th class="text-left py-3 px-4">{{ __('app.bank') }}</th>
                <th class="text-left py-3 px-4">{{ __('app.transfer_reference') }}</th>
                <th class="text-left py-3 px-4">{{ __('app.submitted_at') }}</th>
                <th class="text-left py-3 px-4">{{ __('app.status') }}</th>
                <th class="text-right py-3 px-5"></th>
            </tr>
        </thead>
        <tbody class="divide-y">
            @forelse($topups as $t)
            <tr class="hover:bg-gray-50">
                <td class="py-3 px-5 text-gray-500">#{{ $t->id }}</td>
                <td class="py-3 px-4">
                    <p class="font-semibold text-gray-900">{{ $t->user->name }}</p>
                    <p class="text-xs text-gray-500">{{ $t->user->email }}</p>
                </td>
                <td class="py-3 px-4 text-right font-bold text-gray-900">฿{{ number_format($t->amount,2) }}</td>
                <td class="py-3 px-4 text-gray-700">{{ optional($t->bankAccount)->bank_name ?? '-' }}</td>
                <td class="py-3 px-4 font-mono text-gray-700">{{ $t->transfer_reference ?? '-' }}</td>
                <td class="py-3 px-4 text-gray-500 text-xs">{{ $t->created_at->format('d/m/y H:i') }}</td>
                <td class="py-3 px-4">
                    @php
                        $badge = match($t->status) {
                            'pending_review' => 'bg-yellow-100 text-yellow-800',
                            'approved', 'paid' => 'bg-green-100 text-green-800',
                            'rejected' => 'bg-red-100 text-red-800',
                            default => 'bg-gray-100 text-gray-700',
                        };
                    @endphp
                    <span class="text-xs font-semibold px-2 py-1 rounded-full {{ $badge }}">{{ $t->status }}</span>
                </td>
                <td class="py-3 px-5 text-right">
                    <a href="{{ route('admin.topups.show', $t) }}" class="text-blue-600 hover:text-blue-800 font-semibold text-xs">
                        {{ __('app.review') }} <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center py-16 text-gray-400">{{ __('app.no_topups_here') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($topups->hasPages())<div class="p-4 border-t">{{ $topups->links() }}</div>@endif
</div>
@endsection
