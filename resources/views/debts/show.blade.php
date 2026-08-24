@extends('layouts.admin')

@section('title', $customer->name . ' - Ledger')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header :title="$customer->name" subtitle="Debt ledger and payment history" />

<div class="mb-6 grid grid-cols-2 gap-4">
    <x-stat-card label="Outstanding" :value="format_money($customer->outstanding_balance)" accent="amber" />
    <x-stat-card label="Credit Limit" :value="format_money($customer->credit_limit)" accent="indigo" />
</div>

<x-card class="mb-6">
    <h2 class="mb-4 text-sm font-semibold text-gray-900">Record Payment</h2>
    <form method="POST" action="{{ tenant_route('tenant.debts.payment', ['customer' => $customer]) }}" class="space-y-4">
        @csrf
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="sm:col-span-2">
                <x-input type="number" step="0.01" name="amount" placeholder="Amount" required />
            </div>
            <x-button variant="success" type="submit">Record Payment</x-button>
        </div>
        <x-input type="text" name="description" placeholder="Note (optional)" />
    </form>
</x-card>

<h2 class="mb-4 text-sm font-semibold text-gray-900">Ledger History</h2>

<div class="space-y-3">
    @forelse($entries as $entry)
        <x-card :padding="false" class="p-4">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <x-badge :color="$entry->type === 'payment' ? 'green' : ($entry->type === 'debit' ? 'red' : 'gray')">{{ ucfirst($entry->type) }}</x-badge>
                    <p class="mt-2 text-sm text-gray-700">{{ $entry->description }}</p>
                    <p class="mt-1 text-xs text-gray-500">{{ $entry->created_at->format('M d, H:i') }} · {{ optional($entry->user)->name }}</p>
                </div>
                <div class="text-right shrink-0">
                    <p class="font-semibold text-gray-900">@money($entry->amount)</p>
                    <p class="text-xs text-gray-500">Bal: @money($entry->balance_after)</p>
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No ledger entries.</x-card>
    @endforelse
</div>

<div class="mt-6">{{ $entries->links() }}</div>
@endsection
