@extends('layouts.admin')

@section('title', 'Vendors')
@section('container_class', 'max-w-3xl')

@section('content')
<x-page-header title="Vendors" subtitle="Supplier profiles, contact details, and open credit balances">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.bills.index') }}">Bills</x-button>
    </x-slot>
</x-page-header>

<x-card class="mb-6">
    <h2 class="text-sm font-semibold text-gray-900">Add vendor</h2>
    <form method="POST" action="{{ tenant_route('tenant.supplier-credit.suppliers.store') }}" class="mt-4 space-y-4">
        @csrf
        @include('supplier-credit.suppliers._form')
        <x-button variant="primary" type="submit">Add vendor</x-button>
    </form>
</x-card>

<div class="space-y-3">
    @forelse($suppliers as $supplier)
        <x-card class="!p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="font-semibold text-gray-900">{{ $supplier->name }}</p>
                        @unless($supplier->is_active)
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Inactive</span>
                        @endunless
                    </div>
                    @if($supplier->phone)
                        <p class="text-xs text-gray-500">{{ $supplier->phone }}</p>
                    @endif
                    @if($supplier->email)
                        <p class="text-xs text-gray-500">{{ $supplier->email }}</p>
                    @endif
                </div>
                <div class="flex shrink-0 flex-col items-end gap-2">
                    <div class="text-right">
                        <p class="text-xs text-gray-500">Open balance</p>
                        <p class="text-sm font-semibold text-amber-800">@money($supplier->open_balance)</p>
                    </div>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.vendors.edit', ['supplier' => $supplier]) }}">Edit</x-button>
                        @include('supplier-credit.partials.delete-button', [
                            'action' => tenant_route('tenant.supplier-credit.vendors.destroy', ['supplier' => $supplier]),
                            'confirm' => 'Are you sure you want to delete this vendor? Linked bills and payments will be hidden. Stock already received will not change.',
                            'label' => 'Delete',
                        ])
                    </div>
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No vendors yet.</x-card>
    @endforelse
</div>
@endsection
