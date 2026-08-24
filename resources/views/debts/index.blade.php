@extends('layouts.admin')

@section('title', 'Customer Debts')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Credit Customers" subtitle="Hardware credit buyers and outstanding balances">
    <x-slot name="actions">
        <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.debts.create') }}">+ Add Customer</x-button>
    </x-slot>
</x-page-header>

<div class="space-y-3">
    @forelse($customers as $customer)
        <a href="{{ tenant_route('tenant.debts.show', ['customer' => $customer]) }}" class="block transition hover:opacity-90">
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
        <x-card class="text-center text-sm text-gray-500">No credit customers yet.</x-card>
    @endforelse
</div>

<div class="mt-6">{{ $customers->links() }}</div>
@endsection
