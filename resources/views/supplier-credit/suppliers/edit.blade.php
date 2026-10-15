@extends('layouts.admin')

@section('title', 'Edit Vendor')
@section('container_class', 'max-w-3xl')

@section('content')
@if(session('success'))
    <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
@endif

<x-page-header title="Edit vendor" subtitle="{{ $supplier->name }}">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.vendors.index') }}">All vendors</x-button>
    </x-slot>
</x-page-header>

<x-card>
    <form method="POST" action="{{ tenant_route('tenant.supplier-credit.vendors.update', ['supplier' => $supplier]) }}" class="space-y-4">
        @csrf
        @method('PUT')
        @include('supplier-credit.suppliers._form', ['supplier' => $supplier])
        <div class="flex flex-wrap gap-3 pt-2">
            <x-button variant="primary" type="submit">Save changes</x-button>
            <x-button variant="secondary" href="{{ tenant_route('tenant.supplier-credit.vendors.index') }}">Cancel</x-button>
        </div>
    </form>
</x-card>

@if($hasBills)
    <x-card class="mt-6 !p-4">
        <form method="POST" action="{{ tenant_route('tenant.supplier-credit.vendors.destroy', ['supplier' => $supplier]) }}" class="mt-0">
            @csrf
            @method('DELETE')
            <x-button variant="danger" size="sm" type="button"
                      data-delete-confirm
                      data-delete-message="Delete this vendor?">
                Delete vendor
            </x-button>
        </form>
    </x-card>
@else
    <x-card class="mt-6 !p-4">
        <form method="POST" action="{{ tenant_route('tenant.supplier-credit.vendors.destroy', ['supplier' => $supplier]) }}">
            @csrf
            @method('DELETE')
            <x-button variant="danger" size="sm" type="button"
                      data-delete-confirm
                      data-delete-message="Delete this vendor?">
                Delete vendor
            </x-button>
        </form>
    </x-card>
@endif

@can('adjust-wallet-transactions')
    <div class="mt-4">
        <x-button variant="secondary" size="sm" type="button"
                  data-open-opening-correction
                  data-modal="supplier-opening-correction"
                  data-action="{{ tenant_route('tenant.supplier-credit.vendors.opening-balance', ['supplier' => $supplier]) }}"
                  data-entity="{{ $supplier->name }}"
                  data-current="{{ format_money($supplier->opening_balance) }}"
                  data-value="{{ number_format((float) $supplier->opening_balance, 2, '.', '') }}">Correct opening balance</x-button>
    </div>
    @push('modals')
        @include('partials.opening-correction-modal', [
            'modalId' => 'supplier-opening-correction',
            'title' => 'Correct opening balance',
            'fieldName' => 'opening_balance',
            'fieldLabel' => 'New opening balance',
            'step' => '0.01',
            'currentLabel' => 'Current opening balance',
            'warning' => 'This updates only this vendor\'s opening bill and opening balance. Other vendors, their bills, and inventory stock are not changed.',
        ])
    @endpush
@endcan
@endsection
