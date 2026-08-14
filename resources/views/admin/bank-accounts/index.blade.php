@extends('layouts.app')
@section('title', __('app.bank_accounts'))
@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ __('app.bank_accounts') }}</h2>
        <p class="text-gray-500 mt-1 text-sm">{{ __('app.bank_accounts_intro') }}</p>
    </div>
</div>

@if(session('success'))<div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-3 mb-4 text-sm">{{ session('success') }}</div>@endif
@if(session('warning'))<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-xl p-3 mb-4 text-sm">{{ session('warning') }}</div>@endif

<div class="grid lg:grid-cols-3 gap-6">
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
        <h3 class="font-bold text-gray-900 mb-4">{{ __('app.add_bank_account') }}</h3>
        <form method="POST" action="{{ route('admin.bank-accounts.store') }}" class="space-y-3">
            @csrf
            <input type="text" name="bank_name" required maxlength="100" placeholder="{{ __('app.bank_name') }}" class="w-full border rounded-xl px-4 py-2.5 text-sm">
            <input type="text" name="account_number" required maxlength="50" placeholder="{{ __('app.account_number') }}" class="w-full border rounded-xl px-4 py-2.5 text-sm font-mono">
            <input type="text" name="account_name" required maxlength="200" placeholder="{{ __('app.account_name') }}" class="w-full border rounded-xl px-4 py-2.5 text-sm">
            <input type="text" name="branch" maxlength="100" placeholder="{{ __('app.branch_optional') }}" class="w-full border rounded-xl px-4 py-2.5 text-sm">
            <textarea name="notes" maxlength="500" rows="2" placeholder="{{ __('app.notes_optional') }}" class="w-full border rounded-xl px-4 py-2.5 text-sm"></textarea>
            <input type="number" name="sort_order" min="0" max="9999" value="0" placeholder="{{ __('app.sort_order') }}" class="w-full border rounded-xl px-4 py-2.5 text-sm">
            <label class="flex items-center gap-2 text-sm text-gray-700">
                <input type="checkbox" name="active" value="1" checked> {{ __('app.active') }}
            </label>
            @if($errors->any())<div class="text-xs text-red-600">@foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach</div>@endif
            <button class="w-full bg-blue-600 text-white py-2.5 rounded-xl font-semibold hover:bg-blue-700">
                <i class="fas fa-plus mr-2"></i>{{ __('app.add') }}
            </button>
        </form>
    </div>

    <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-5 border-b">
            <h3 class="font-bold text-gray-900">{{ __('app.all_bank_accounts') }} ({{ $accounts->count() }})</h3>
        </div>
        <div class="divide-y">
            @forelse($accounts as $ba)
            <div class="p-5 flex items-start gap-4 {{ $ba->active ? '' : 'opacity-50' }}">
                <div class="flex-1">
                    <p class="font-semibold text-gray-900">{{ $ba->bank_name }}
                        @if(!$ba->active)<span class="text-xs bg-gray-200 text-gray-600 px-2 py-0.5 rounded-full ml-2">{{ __('app.inactive') }}</span>@endif
                    </p>
                    <p class="text-sm font-mono text-gray-700">{{ $ba->account_number }}</p>
                    <p class="text-sm text-gray-500">{{ $ba->account_name }}@if($ba->branch) &middot; {{ $ba->branch }}@endif</p>
                    @if($ba->notes)<p class="text-xs text-gray-400 mt-1">{{ $ba->notes }}</p>@endif
                    <p class="text-xs text-gray-400 mt-1">{{ __('app.sort_order') }}: {{ $ba->sort_order }}</p>
                </div>
                <div class="flex flex-col gap-2">
                    <form method="POST" action="{{ route('admin.bank-accounts.toggle', $ba) }}">
                        @csrf
                        <button class="text-xs px-3 py-1.5 rounded-lg {{ $ba->active ? 'bg-gray-100 text-gray-700' : 'bg-green-100 text-green-800' }}">
                            {{ $ba->active ? __('app.deactivate') : __('app.activate') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('admin.bank-accounts.destroy', $ba) }}" onsubmit="return confirm('{{ __('app.confirm_delete') }}')">
                        @csrf @method('DELETE')
                        <button class="text-xs px-3 py-1.5 rounded-lg bg-red-50 text-red-700 hover:bg-red-100"><i class="fas fa-trash mr-1"></i>{{ __('app.delete') }}</button>
                    </form>
                </div>
            </div>
            @empty
            <div class="p-10 text-center text-gray-400">{{ __('app.no_bank_accounts_yet') }}</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
