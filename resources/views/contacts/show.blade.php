@extends('layouts.admin')

@section('title', $customer->name)

@section('content')
<x-page-header :title="$customer->name" subtitle="{{ $customer->company_name ?? 'Contact profile' }}">
    <x-slot name="actions">
        @can('update', $customer)
            <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.contacts.edit', ['customer' => $customer]) }}">Edit</x-button>
        @endcan
    </x-slot>
</x-page-header>

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    @if($customer->is_credit_customer)
        <x-stat-card label="Outstanding" :value="format_money($customer->outstanding_balance)" accent="amber" />
        <x-stat-card label="Credit Limit" :value="format_money($customer->credit_limit)" accent="indigo" />
    @endif
    <x-stat-card label="Phone" :value="$customer->phone ?? '—'" accent="indigo" />
</div>

@if($customer->email || $customer->address || $customer->notes)
    <x-card class="mb-6 text-sm text-gray-700">
        @if($customer->email)<p><span class="font-medium text-gray-900">Email:</span> {{ $customer->email }}</p>@endif
        @if($customer->address)<p class="mt-2"><span class="font-medium text-gray-900">Address:</span> {{ $customer->address }}</p>@endif
        @if($customer->notes)<p class="mt-2"><span class="font-medium text-gray-900">Notes:</span> {{ $customer->notes }}</p>@endif
    </x-card>
@endif

@if($customer->is_credit_customer)
    <x-card class="mb-6">
        <h2 class="mb-4 text-sm font-semibold text-gray-900">Record Payment</h2>
        <form method="POST" action="{{ tenant_route('tenant.contacts.payment', ['customer' => $customer]) }}" class="space-y-4">
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
@endif

<h2 class="mb-4 text-sm font-semibold text-gray-900">Activity</h2>
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
                    @if($customer->is_credit_customer)
                        <p class="text-xs text-gray-500">Bal: @money($entry->balance_after)</p>
                    @endif
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No activity recorded.</x-card>
    @endforelse
</div>

<div class="mt-6">{{ $entries->links() }}</div>
@endsection
