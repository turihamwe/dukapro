@extends('layouts.admin')

@section('title', 'Wallets & accounts')

@section('content')
<x-page-header title="Wallets & payment accounts" subtitle="Track cash, mobile money, and bank balances in one place">
    <x-slot name="actions">
        @if($wallets->where('is_active', true)->isNotEmpty())
            <x-button variant="primary" size="sm" type="button" onclick="openAppModal('wallet-deposit-modal')">+ Add funds</x-button>
        @endif
        @can('access-customer-credit')
            <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.payments.index') }}">Receivables</x-button>
        @endcan
        @can('access-supplier-credit')
            <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.payments.index') }}">Payables</x-button>
        @endcan
    </x-slot>
</x-page-header>

<x-stat-card class="mb-6" label="Total liquid funds (active wallets)" :value="format_money($totalLiquid)" accent="emerald" />

<div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @forelse($wallets->where('is_active', true) as $wallet)
        <x-card class="p-4">
            <p class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ $wallet->typeLabel() }}</p>
            <p class="mt-1 text-lg font-semibold text-gray-900">{{ $wallet->name }}</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700">@money($wallet->current_balance)</p>
            @if((float) $wallet->opening_balance !== (float) $wallet->current_balance)
                <p class="mt-1 text-xs text-gray-500">Started at @money($wallet->opening_balance)</p>
            @endif
            <x-button variant="secondary" size="sm" type="button" class="mt-3 w-full" onclick="openWalletDepositModal({{ (int) $wallet->id }})">Deposit</x-button>
        </x-card>
    @empty
        <x-card class="sm:col-span-2 lg:col-span-3 text-sm text-gray-600">
            No active payment accounts yet. Balances appear here once accounts exist and receivable or payable payments are linked to wallets.
        </x-card>
    @endforelse
</div>

@if($wallets->isNotEmpty())
    <h2 class="mb-3 text-sm font-semibold text-gray-900">All accounts</h2>
    <div class="mb-2 space-y-3">
        @foreach($wallets as $wallet)
            <x-card :padding="false" class="p-4">
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
                    <div>
                        <p class="mb-1 text-xs font-medium text-gray-700">Name</p>
                        <p class="text-sm font-semibold text-gray-900">{{ $wallet->name }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-medium text-gray-700">Type</p>
                        <p class="text-sm text-gray-900">{{ $wallet->typeLabel() }}</p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-medium text-gray-700">Current balance</p>
                        <p class="text-sm font-semibold text-emerald-700">@money($wallet->current_balance)</p>
                    </div>
                    <div>
                        <p class="mb-1 text-xs font-medium text-gray-700">Status</p>
                        <p class="text-sm text-gray-900">
                            @if($wallet->is_active)
                                <span class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">Active</span>
                            @else
                                <span class="inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Inactive</span>
                            @endif
                        </p>
                    </div>
                </div>
            </x-card>
        @endforeach
    </div>
@endif

<div class="mt-4">
    <div class="mb-3 h-px w-14 bg-gray-200" aria-hidden="true"></div>
    <x-card class="mb-8">
    <h2 class="mb-4 text-sm font-semibold text-gray-900">Add wallet</h2>
    <form method="POST" action="{{ tenant_route('tenant.wallets.store') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 lg:items-end">
        @csrf
        <x-input type="text" name="name" label="Name" placeholder="e.g. MTN Mobile Money" required />
        <x-select name="type" label="Type" required>
            @foreach($types as $value => $typeLabel)
                <option value="{{ $value }}" @selected(old('type', 'cash') === $value)>{{ $typeLabel }}</option>
            @endforeach
        </x-select>
        <x-input type="number" step="0.01" min="0" name="opening_balance" label="Opening balance" value="{{ old('opening_balance', '0') }}" />
        <x-button variant="primary" type="submit">Add wallet</x-button>
    </form>
    </x-card>
</div>

@if($recentInflows->isNotEmpty())
    <x-card class="mb-8 !p-0 overflow-hidden">
        <div class="border-b border-gray-100 px-4 py-3">
            <h2 class="text-sm font-semibold text-gray-900">Recent manual deposits</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 text-sm">
                <thead class="bg-gray-50 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
                    <tr>
                        <th class="px-4 py-2">When</th>
                        <th class="px-4 py-2">Wallet</th>
                        <th class="px-4 py-2">Category</th>
                        <th class="px-4 py-2">Source / note</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50 bg-white">
                    @foreach($recentInflows as $inflow)
                        <tr>
                            <td class="px-4 py-2 text-gray-600">{{ $inflow->occurred_at->format('M j, Y g:i A') }}</td>
                            <td class="px-4 py-2 text-gray-900">{{ $inflow->wallet->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-gray-700">{{ $inflow->categoryLabel() }}</td>
                            <td class="px-4 py-2 text-gray-600">{{ $inflow->source_name ?: '—' }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-emerald-700">+@money($inflow->amount)</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
@endif

@push('modals')
    @if($wallets->where('is_active', true)->isNotEmpty())
        @include('wallets._deposit-modal', ['wallets' => $wallets, 'inflowCategories' => $inflowCategories ?? []])
    @endif
@endpush

@push('scripts')
<script>
window.openWalletDepositModal = function (walletId) {
    var select = document.querySelector('#wallet-deposit-modal select[name="payment_wallet_id"]');
    if (select && walletId) {
        select.value = String(walletId);
    }
    if (typeof window.openAppModal === 'function') {
        window.openAppModal('wallet-deposit-modal');
    }
};
@if($errors->any() && (old('payment_wallet_id') || old('category')))
(function () {
    var modal = document.getElementById('wallet-deposit-modal');
    if (modal) window.openAppModal(modal);
})();
@endif
</script>
@endpush
@endsection
