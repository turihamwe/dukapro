@extends('layouts.admin')

@section('title', 'Customer Debts')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Credit Customers" subtitle="Hardware credit buyers and outstanding balances">
    <x-slot name="actions">
        <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.debts.create') }}">+ Add Customer</x-button>
    </x-slot>
</x-page-header>

<x-sortable-table>
    <x-sortable-list-bar :columns="['name' => 'Name', 'balance' => 'Outstanding', 'limit' => 'Credit limit']" />
    <div class="space-y-3" x-ref="tbody">
    @forelse($customers as $customer)
        <a href="{{ tenant_route('tenant.debts.show', ['customer' => $customer]) }}"
           class="block transition hover:opacity-90"
           data-sortable-row
           data-sort-name="{{ strtolower($customer->name) }}"
           data-sort-balance="{{ (float) $customer->outstanding_balance }}"
           data-sort-limit="{{ (float) $customer->credit_limit }}">
            <x-card :padding="false" class="p-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-medium text-gray-900">{{ $customer->name }}</p>
                        <p class="text-xs text-gray-500">{{ $customer->phone ?? 'No phone' }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="font-semibold text-red-600">@money($customer->outstanding_balance)</p>
                        <p class="text-xs text-gray-500">Limit: @money($customer->credit_limit)</p>
                    </div>
                </div>
            </x-card>
        </a>
    @empty
        <div data-sort-empty="1">
            <x-card class="text-center text-sm text-gray-500">No credit customers yet.</x-card>
        </div>
    @endforelse
    </div>
</x-sortable-table>

<div class="mt-6">{{ $customers->links() }}</div>
@endsection
