@extends('layouts.app')

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
                        <p class="font-medium text-gray-900 dark:text-white">{{ $customer->name }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ $customer->phone ?? 'No phone' }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        <p class="font-semibold text-red-600 dark:text-red-400">{{ number_format($customer->outstanding_balance, 2) }}</p>
                        <p class="text-xs text-gray-500">Limit: {{ number_format($customer->credit_limit, 2) }}</p>
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
