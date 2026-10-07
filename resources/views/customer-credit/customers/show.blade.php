@extends('layouts.admin')

@section('title', $customer->name)

@section('content')
<x-page-header :title="$customer->name" subtitle="Customer receivables profile">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.customers.edit', ['customer' => $customer]) }}">Edit</x-button>
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.customers.index') }}">All customers</x-button>
    </x-slot>
</x-page-header>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <x-stat-card label="Outstanding" :value="format_money($customer->outstanding_balance)" accent="amber" />
    <x-stat-card label="Credit limit" :value="format_money($customer->credit_limit)" accent="indigo" />
    <x-stat-card label="Terms" :value="($customer->payment_terms_days ?? 30) . ' days'" accent="gray" />
</div>

<x-card class="mb-6">
    <h2 class="mb-4 text-sm font-semibold text-gray-900">Record payment received</h2>
    <form method="POST" action="{{ tenant_route('tenant.customer-credit.customers.payments.store', ['customer' => $customer]) }}" class="space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <x-input type="number" step="0.01" name="amount" placeholder="Amount" required />
            </div>
            <x-button variant="success" type="submit">Record payment</x-button>
        </div>
        <x-input type="text" name="description" placeholder="Note (optional)" />
    </form>
</x-card>

<h2 class="mb-3 text-sm font-semibold text-gray-900">Ledger</h2>
<div class="space-y-3 mb-8">
    @forelse($entries as $entry)
        <x-card :padding="false" class="p-4">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <x-badge :color="$entry->type === 'payment' ? 'green' : 'red'">{{ ucfirst($entry->type) }}</x-badge>
                    @if($entry->is_opening_balance)
                        <x-badge color="gray" class="ml-1">Opening</x-badge>
                    @endif
                    <p class="mt-2 text-sm text-gray-700">{{ $entry->description }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $entry->created_at->format('M d, H:i') }}</p>
                </div>
                <p class="font-semibold text-gray-900">@money($entry->amount)</p>
            </div>
        </x-card>
    @empty
        <x-card class="text-sm text-gray-500">No ledger activity yet.</x-card>
    @endforelse
</div>
{{ $entries->links() }}

@if($creditSales->isNotEmpty())
    <h2 class="mb-3 mt-8 text-sm font-semibold text-gray-900">Recent credit sales</h2>
    <div class="space-y-2">
        @foreach($creditSales as $sale)
            <a href="{{ tenant_route('tenant.customer-credit.invoices.show', ['sale' => $sale]) }}" class="block text-sm text-indigo-600 hover:text-indigo-800">
                #{{ $sale->sale_number ?? $sale->id }} · @money($sale->total_amount) · {{ $sale->created_at->format('M d, Y') }}
            </a>
        @endforeach
    </div>
@endif
@endsection
