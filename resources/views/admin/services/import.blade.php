@extends('layouts.app')
@section('title', __('app.import_from_provider'))
@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-gray-900">{{ __('app.import_from_provider') }}</h2>
        <p class="text-sm text-gray-500 mt-1">
            {{ __('app.provider_catalog_intro') }}
            &middot; <a href="{{ route('admin.services.index') }}" class="text-blue-600 hover:underline">{{ __('app.services') }}</a>
        </p>
    </div>
    <a href="{{ route('admin.services.import.index', ['refresh' => 1]) }}"
       class="text-sm bg-white border border-gray-200 hover:border-blue-400 rounded-xl px-4 py-2 font-semibold">
        <i class="fas fa-arrows-rotate mr-1"></i>{{ __('app.refresh_from_provider') }}
    </a>
</div>

@if(session('success'))<div class="bg-green-50 border border-green-200 text-green-800 rounded-xl p-3 mb-4 text-sm">{{ session('success') }}</div>@endif
@if(session('error'))<div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-3 mb-4 text-sm">{{ session('error') }}</div>@endif
@if($providerError)<div class="bg-red-50 border border-red-200 text-red-800 rounded-xl p-4 mb-4 text-sm"><i class="fas fa-triangle-exclamation mr-2"></i>{{ $providerError }}</div>@endif

<div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-4 text-sm">
    <div class="bg-white border border-gray-100 rounded-xl p-4">
        <p class="text-xs text-gray-500 uppercase">{{ __('app.provider_services') }}</p>
        <p class="text-2xl font-bold text-gray-900">{{ $providerCount }}</p>
    </div>
    <div class="bg-white border border-gray-100 rounded-xl p-4">
        <p class="text-xs text-gray-500 uppercase">{{ __('app.imported_locally') }}</p>
        <p class="text-2xl font-bold text-blue-600">{{ $importedCount }}</p>
    </div>
    <div class="bg-white border border-gray-100 rounded-xl p-4">
        <p class="text-xs text-gray-500 uppercase">{{ __('app.missing_at_provider') }}</p>
        <p class="text-2xl font-bold {{ $missingLocally > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $missingLocally }}</p>
    </div>
    <form method="GET" action="{{ route('admin.services.import.index') }}" class="bg-white border border-gray-100 rounded-xl p-4 flex items-center gap-2">
        <i class="fas fa-search text-gray-400"></i>
        <input type="text" name="q" value="{{ $search }}" placeholder="{{ __('app.search_provider_service') }}" class="flex-1 outline-none text-sm">
    </form>
</div>

<form method="POST" action="{{ route('admin.services.import.sync') }}" x-data="{ selected: [] }">
    @csrf

    {{-- Bulk pricing controls --}}
    <div class="bg-white border border-gray-100 rounded-2xl p-5 mb-4 grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">{{ __('app.usd_to_thb') }}</label>
            <div class="relative">
                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm">฿</span>
                <input type="number" name="rate" min="0.01" step="0.01" value="{{ $rate }}" required
                       class="w-full pl-7 pr-3 py-2 border border-gray-200 rounded-xl text-sm">
            </div>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 uppercase mb-1">{{ __('app.markup_multiplier') }}</label>
            <input type="number" name="markup" min="1" max="20" step="0.1" value="{{ $markup }}" required
                   class="w-full px-3 py-2 border border-gray-200 rounded-xl text-sm">
            <p class="text-xs text-gray-400 mt-1">{{ __('app.markup_hint') }}</p>
        </div>
        <div class="flex items-end gap-3">
            <button type="submit"
                    :disabled="selected.length === 0"
                    :class="selected.length === 0 ? 'opacity-40 cursor-not-allowed' : 'hover:bg-blue-700'"
                    class="flex-1 bg-blue-600 text-white font-semibold py-2.5 rounded-xl transition">
                <i class="fas fa-download mr-2"></i>
                <span x-text="selected.length === 0 ? '{{ __('app.select_services_to_import') }}' : '{{ __('app.import_selected') }} (' + selected.length + ')'"></span>
            </button>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b text-xs text-gray-500 uppercase">
                <tr>
                    <th class="px-4 py-3 w-10"></th>
                    <th class="text-left px-4 py-3">ID</th>
                    <th class="text-left px-4 py-3">{{ __('app.name') }}</th>
                    <th class="text-center px-3 py-3">{{ __('app.device') }}</th>
                    <th class="text-right px-3 py-3">USD</th>
                    <th class="text-right px-3 py-3">{{ __('app.preview_cost') }}</th>
                    <th class="text-right px-3 py-3">{{ __('app.preview_sell') }}</th>
                    <th class="text-center px-3 py-3">{{ __('app.serial') }}</th>
                    <th class="text-left px-3 py-3">{{ __('app.status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($rows as $r)
                    @php $imported = $r['local'] !== null; @endphp
                    <tr class="hover:bg-gray-50 {{ $imported ? 'bg-gray-50/60' : '' }}">
                        <td class="px-4 py-3">
                            <input type="checkbox" name="provider_ids[]" value="{{ $r['provider_service_id'] }}"
                                   x-model="selected"
                                   @if($imported) title="{{ __('app.already_imported_will_update') }}" @endif>
                        </td>
                        <td class="px-4 py-3">
                            <code class="bg-gray-100 px-2 py-1 rounded text-xs">{{ $r['provider_service_id'] }}</code>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-semibold text-gray-900">{{ $r['clean_name'] ?: $r['name'] }}</p>
                            @if($r['clean_name'] !== $r['name'])
                                <p class="text-xs text-gray-400">{{ $r['name'] }}</p>
                            @endif
                            @if($r['description'])
                                <p class="text-xs text-gray-500 mt-0.5 line-clamp-2">{{ $r['description'] }}</p>
                            @endif
                            @if($r['processing_time'])
                                <p class="text-xs text-gray-400 mt-0.5"><i class="far fa-clock mr-1"></i>{{ $r['processing_time'] }}</p>
                            @endif
                        </td>
                        <td class="px-3 py-3 text-center">
                            <span class="px-2 py-0.5 rounded-full text-xs bg-blue-50 text-blue-700">{{ strtoupper($r['inferred_device']) }}</span>
                        </td>
                        <td class="px-3 py-3 text-right text-gray-700">${{ number_format($r['usd_price'], 2) }}</td>
                        <td class="px-3 py-3 text-right text-gray-700">฿{{ number_format($r['preview_cost'], 2) }}</td>
                        <td class="px-3 py-3 text-right font-bold text-blue-600">฿{{ number_format($r['preview_sell'], 0) }}</td>
                        <td class="px-3 py-3 text-center text-xs">
                            @if($r['supports_serial'] === true)  <i class="fas fa-check text-green-600"></i>
                            @elseif($r['supports_serial'] === false) <i class="fas fa-xmark text-gray-400"></i>
                            @else &mdash; @endif
                        </td>
                        <td class="px-3 py-3">
                            @if($imported)
                                <span class="text-xs font-semibold text-gray-600 bg-gray-200 px-2 py-1 rounded-full">
                                    <i class="fas fa-check mr-1"></i>{{ __('app.imported') }}
                                </span>
                                <p class="text-xs text-gray-500 mt-1">
                                    {{ __('app.local_sell') }}: <span class="font-bold">฿{{ number_format($r['local']->sell_price, 0) }}</span>
                                </p>
                            @else
                                <span class="text-xs font-semibold text-blue-700 bg-blue-50 px-2 py-1 rounded-full">
                                    <i class="fas fa-plus mr-1"></i>{{ __('app.new') }}
                                </span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center py-16 text-gray-400">
                        @if($providerError)
                            {{ __('app.provider_unreachable') }}
                        @else
                            {{ __('app.no_provider_services') }}
                        @endif
                    </td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</form>
@endsection
