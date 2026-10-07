@extends('layouts.admin')

@section('title', 'Edit Vendor')
@section('container_class', 'max-w-3xl')

@section('content')
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
    <x-card class="mt-6 !p-4 border-amber-100 bg-amber-50/50">
        <p class="text-sm text-amber-950">
            This vendor has bill history. Removing them will <strong>deactivate</strong> instead of deleting records.
        </p>
        <form method="POST" action="{{ tenant_route('tenant.supplier-credit.vendors.destroy', ['supplier' => $supplier]) }}" class="mt-3">
            @csrf
            @method('DELETE')
            <x-button variant="danger" size="sm" type="button"
                      data-delete-confirm
                      data-delete-message="Are you sure you want to delete this item?"
                      data-delete-detail="Deactivate this vendor? They will be hidden from new restocks but bills stay on file.">
                Deactivate vendor
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
                      data-delete-message="Are you sure you want to delete this item?"
                      data-delete-detail="Permanently remove this vendor?">
                Delete vendor
            </x-button>
        </form>
    </x-card>
@endif
@endsection
