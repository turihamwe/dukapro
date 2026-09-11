@extends('layouts.superadmin')

@section('title', $config['label'])

@section('content')
<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ $config['label'] }}</h1>
        <p class="mt-1 text-sm text-gray-500">Platform-wide {{ strtolower($config['label']) }} management</p>
    </div>
    @if(($config['creatable'] ?? false))
        <a href="{{ route('superadmin.entities.create', $entity) }}"
           class="inline-flex items-center justify-center rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">
            + Add {{ rtrim($config['label'], 's') }}
        </a>
    @endif
</div>

@if($entity === 'shareholders' && ! empty($shareStats))
    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Shares allocated</p>
            <p class="mt-2 text-2xl font-bold">{{ number_format($shareStats['allocated_shares'], 2) }} / {{ number_format($shareStats['total_shares'], 0) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Shares remaining</p>
            <p class="mt-2 text-2xl font-bold text-violet-600">{{ number_format($shareStats['remaining_shares'], 2) }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Shareholders</p>
            <p class="mt-2 text-2xl font-bold">{{ $shareStats['shareholder_count'] }} / {{ $shareStats['max_shareholders'] }}</p>
        </div>
        <div class="rounded-xl border border-gray-200 bg-white p-4">
            <p class="text-xs uppercase text-gray-500">Price per share</p>
            <p class="mt-2 text-2xl font-bold">UGX {{ number_format($shareStats['price_per_share'], 0) }}</p>
        </div>
    </div>
@endif

@if(!empty($supportsSoftDeletes))
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('superadmin.entities.index', array_filter(['entity' => $entity, 'q' => request('q')])) }}"
           class="rounded-lg px-4 py-2 text-sm font-medium {{ empty($showTrashed) ? 'bg-violet-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50' }}">
            Active
        </a>
        <a href="{{ route('superadmin.entities.index', array_filter(['entity' => $entity, 'trashed' => 1, 'q' => request('q')])) }}"
           class="rounded-lg px-4 py-2 text-sm font-medium {{ ! empty($showTrashed) ? 'bg-amber-600 text-white' : 'border border-gray-300 text-gray-700 hover:bg-gray-50' }}">
            Archived
        </a>
    </div>
@endif

<form method="GET" class="mb-4 flex gap-2">
    @if(!empty($showTrashed))
        <input type="hidden" name="trashed" value="1">
    @endif
    <input type="search" name="q" value="{{ request('q') }}" placeholder="Search {{ strtolower($config['label']) }}…"
           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
    <button type="submit" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium hover:bg-gray-50">Filter</button>
</form>

@if(!empty($showTrashed))
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
        Viewing archived (soft-deleted) {{ strtolower($config['label']) }}. These records are hidden from businesses but kept for history such as sales reports.
    </div>
@endif

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 bg-gray-50 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">ID</th>
                    @foreach($config['list'] as $column)
                        <th class="px-4 py-3">{{ str_replace('_', ' ', $column) }}</th>
                    @endforeach
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($records as $record)
                    <tr class="hover:bg-gray-50 {{ ! empty($showTrashed) ? 'bg-amber-50/40' : '' }}">
                        <td class="px-4 py-3 text-gray-500">
                            #{{ $record->id }}
                            @if(! empty($showTrashed) && $record->deleted_at)
                                <span class="mt-1 block text-[10px] font-semibold uppercase tracking-wide text-amber-700">Archived</span>
                            @endif
                        </td>
                        @foreach($config['list'] as $column)
                            <td class="px-4 py-3">
                                @php $value = data_get($record, $column); @endphp
                                @if($column === 'business_id' && $record->relationLoaded('business') && $record->business)
                                    {{ $record->business->name }}
                                @elseif($column === 'affiliate_id' && $record->relationLoaded('affiliate') && $record->affiliate)
                                    {{ $record->affiliate->name }}
                                @elseif($entity === 'businesses' && $column === 'affiliate')
                                    @if($record->relationLoaded('sponsor') && $record->sponsor)
                                        <a href="{{ route('superadmin.entities.show', ['affiliates', $record->sponsor->id]) }}"
                                           class="font-medium text-violet-700 hover:text-violet-900">
                                            {{ $record->sponsor->name }}
                                        </a>
                                        <span class="block text-xs text-gray-500">{{ $record->sponsor->code }}</span>
                                        @if($record->relationLoaded('referringAffiliate') && $record->referringAffiliate)
                                            <span class="mt-0.5 block text-xs text-gray-500">
                                                Sub: {{ $record->referringAffiliate->name }} ({{ $record->referringAffiliate->code }})
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                @elseif($column === 'business_type' && $value)
                                    {{ \App\Enums\BusinessType::label($value) }}
                                @elseif($column === 'shareholder_id' && $record->relationLoaded('shareholder') && $record->shareholder)
                                    {{ $record->shareholder->name }}
                                @elseif(in_array($column, ['capital_invested', 'total_earnings', 'amount', 'payment_amount', 'commission_amount'], true))
                                    {{ is_numeric($value) ? 'UGX ' . number_format((float) $value, 0) : $value }}
                                @elseif($column === 'shares_owned')
                                    {{ is_numeric($value) ? number_format((float) $value, 2) : $value }}
                                @elseif($column === 'contract_completed')
                                    {{ $value ? 'Yes' : 'No' }}
                                @elseif($column === 'commission_rate')
                                    {{ is_numeric($value) ? number_format((float) $value * 100, 1) . '%' : $value }}
                                @elseif($value instanceof \Carbon\Carbon)
                                    {{ $value->format('M j, Y') }}
                                @elseif(is_bool($value))
                                    {{ $value ? 'Yes' : 'No' }}
                                @elseif(in_array($column, ['price', 'amount', 'total', 'stock_quantity'], true))
                                    {{ is_numeric($value) ? number_format((float) $value, $column === 'stock_quantity' ? 0 : 2) : $value }}
                                @else
                                    {{ $value }}
                                @endif
                            </td>
                        @endforeach
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            @if($entity === 'users')
                                @can('platform-full-access')
                                    @if(app(\App\Services\UserPromotionService::class)->canPromoteToAffiliate($record))
                                        <form method="POST" action="{{ route('superadmin.users.promote-affiliate', $record) }}" class="inline"
                                              onsubmit="return confirm('Promote {{ $record->name }} to Affiliate?')">
                                            @csrf
                                            <button type="submit" class="text-indigo-600 hover:text-indigo-800" title="Promote to Affiliate">Affiliate</button>
                                        </form>
                                    @endif
                                    @if(app(\App\Services\UserPromotionService::class)->canPromoteToShareholder($record))
                                        <form method="POST" action="{{ route('superadmin.users.promote-shareholder', $record) }}" class="inline ml-2"
                                              onsubmit="return confirm('Promote {{ $record->name }} to Shareholder with {{ $defaultPromotionShares }} share(s)?')">
                                            @csrf
                                            <input type="hidden" name="shares" value="{{ $defaultPromotionShares }}">
                                            <button type="submit" class="text-emerald-600 hover:text-emerald-800" title="Promote to Shareholder">Shareholder</button>
                                        </form>
                                    @endif
                                @endcan
                            @endif
                            @if($entity === 'businesses')
                                @can('platform-full-access')
                                    <form method="POST" action="{{ route('superadmin.impersonate.start', $record->id) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="text-violet-600 hover:text-violet-800">Impersonate</button>
                                    </form>
                                @endcan
                            @endif
                            <a href="{{ route('superadmin.entities.show', [$entity, $record->id]) }}" class="ml-3 text-violet-600 hover:text-violet-800">View</a>
                            @can('platform-full-access')
                                <a href="{{ route('superadmin.entities.edit', [$entity, $record->id]) }}" class="ml-3 text-gray-600 hover:text-gray-900">Edit</a>
                                @if(($config['deletable'] ?? true) && empty($showTrashed))
                                    <form method="POST" action="{{ route('superadmin.entities.destroy', [$entity, $record->id]) }}" class="ml-3 inline" onsubmit="return confirm('Archive this record? It will be hidden but kept for historical data such as sales reports.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-800">Archive</button>
                                    </form>
                                @endif
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($config['list']) + 2 }}" class="px-4 py-10 text-center text-gray-500">No records found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($records->hasPages())
        <div class="border-t border-gray-200 px-4 py-3">{{ $records->links() }}</div>
    @endif
</div>
@endsection
