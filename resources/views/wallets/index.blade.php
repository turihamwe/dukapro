@extends('layouts.admin')

@section('title', 'Wallets & accounts')

@section('content')
<x-page-header title="Wallets & payment accounts" subtitle="Track cash, mobile money, and bank balances in one place">
    <x-slot name="actions">
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
        </x-card>
    @empty
        <x-card class="sm:col-span-2 lg:col-span-3 text-sm text-gray-600">
            No wallets yet. Add your cash till, MTN MoMo, or bank account below to track where money sits.
        </x-card>
    @endforelse
</div>

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

@if($wallets->isNotEmpty())
    <h2 class="mb-3 text-sm font-semibold text-gray-900">Manage wallets</h2>
    <div class="space-y-3">
        @foreach($wallets as $wallet)
            <x-card :padding="false" class="p-4">
                <form method="POST" action="{{ tenant_route('tenant.wallets.update', ['wallet' => $wallet]) }}" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5 lg:items-end">
                    @csrf
                    @method('PUT')
                    <x-input type="text" name="name" label="Name" value="{{ old('name', $wallet->name) }}" required />
                    <x-select name="type" label="Type" required>
                        @foreach($types as $value => $typeLabel)
                            <option value="{{ $value }}" @selected(old('type', $wallet->type) === $value)>{{ $typeLabel }}</option>
                        @endforeach
                    </x-select>
                    <div>
                        <p class="mb-1 text-xs font-medium text-gray-700">Current balance</p>
                        <p class="text-sm font-semibold text-gray-900">@money($wallet->current_balance)</p>
                    </div>
                    <x-input type="number" name="sort_order" label="Sort order" value="{{ old('sort_order', $wallet->sort_order) }}" min="0" max="9999" />
                    <div class="flex flex-col gap-2">
                        <label class="flex items-center gap-2 text-sm text-gray-700">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" class="rounded border-gray-300 text-indigo-600" @checked(old('is_active', $wallet->is_active))>
                            Active
                        </label>
                        <x-button variant="secondary" size="sm" type="submit">Save</x-button>
                    </div>
                </form>
            </x-card>
        @endforeach
    </div>
@endif
@endsection
