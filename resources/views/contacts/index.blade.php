@extends('layouts.admin')

@section('title', 'Contacts')

@section('content')
<x-page-header title="Contacts" subtitle="All your business contacts in one place">
    <x-slot name="actions">
        @can('manage-debts')
            <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.contacts.import.show') }}">Import CSV</x-button>
            <x-button variant="primary" size="sm" href="{{ tenant_route('tenant.contacts.create') }}">+ Add Contact</x-button>
        @endcan
    </x-slot>
</x-page-header>

<div class="mb-4 flex gap-2">
    <a href="{{ tenant_route('tenant.contacts.index', ['filter' => 'all']) }}"
       class="rounded-full border px-3 py-1 text-xs font-medium {{ $filter === 'all' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-200 bg-white text-gray-700' }}">All</a>
    <a href="{{ tenant_route('tenant.contacts.index', ['filter' => 'credit']) }}"
       class="rounded-full border px-3 py-1 text-xs font-medium {{ $filter === 'credit' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-200 bg-white text-gray-700' }}">Credit customers</a>
</div>

<div class="space-y-3">
    @forelse($customers as $customer)
        <a href="{{ tenant_route('tenant.contacts.show', ['customer' => $customer]) }}" class="block transition hover:opacity-90">
            <x-card :padding="false" class="p-4">
                <div class="flex items-center justify-between gap-4">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <p class="font-medium text-gray-900">{{ $customer->name }}</p>
                            @if($customer->is_credit_customer)
                                <x-badge color="amber">Credit</x-badge>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500">{{ $customer->company_name ?? $customer->phone ?? 'No phone' }}</p>
                    </div>
                    <div class="text-right shrink-0">
                        @if($customer->is_credit_customer)
                            <p class="font-semibold text-red-600">@money($customer->outstanding_balance)</p>
                            <p class="text-xs text-gray-500">Limit: @money($customer->credit_limit)</p>
                        @else
                            <p class="text-xs text-gray-500">{{ $customer->email ?? '—' }}</p>
                        @endif
                    </div>
                </div>
            </x-card>
        </a>
    @empty
        <x-card class="text-center text-sm text-gray-500">No contacts yet. Import your address book by adding contacts here.</x-card>
    @endforelse
</div>

<div class="mt-6">{{ $customers->links() }}</div>
@endsection
