@php
    $wallets = $walletSummary['wallets'] ?? collect();
    $totalLiquid = $walletSummary['total_liquid'] ?? 0;
@endphp

@if($wallets->isNotEmpty())
<x-card class="mb-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-gray-900">Payment accounts</h2>
            <p class="mt-1 text-xs text-gray-500">Internal liquidity across cash, mobile money, and bank.</p>
        </div>
        @can('manage-wallets')
            <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.wallets.index') }}">Manage wallets</x-button>
        @endcan
    </div>
    <p class="mt-4 text-2xl font-bold text-emerald-700">{{ format_money($totalLiquid) }}</p>
    <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Total liquid funds</p>
    <ul class="mt-4 divide-y divide-gray-100 border-t border-gray-100">
        @foreach($wallets as $wallet)
            <li class="flex items-center justify-between gap-3 py-2 text-sm">
                <span class="text-gray-700">{{ $wallet->name }} <span class="text-gray-400">· {{ $wallet->typeLabel() }}</span></span>
                <span class="font-semibold text-gray-900">@money($wallet->current_balance)</span>
            </li>
        @endforeach
    </ul>
</x-card>
@endif
