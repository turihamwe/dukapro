@extends('layouts.admin')

@section('title', 'Vendors')
@section('container_class', 'max-w-3xl')

@section('content')
<x-page-header title="Vendors" subtitle="Supplier profiles, contact details, and open credit balances">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" type="button" data-open-import-suppliers>Import (CSV)</x-button>
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.suppliers.export') }}">Export (CSV)</x-button>
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.bills.index') }}">Bills</x-button>
    </x-slot>
</x-page-header>

@if(session('import_errors') && count(session('import_errors')))
    <x-alert type="warning" class="mb-4">
        <p class="font-medium">Import notes:</p>
        <ul class="mt-2 list-inside list-disc text-xs">
            @foreach(session('import_errors') as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif

@if(session('success'))
    <x-alert type="success" class="mb-4">{{ session('success') }}</x-alert>
@endif

<p class="mb-4 text-xs text-gray-500">CSV export includes <strong>all vendors</strong> for this business (full list, not paginated).</p>

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
                        @if($supplier->open_balance > 0)
                            <x-button
                                variant="primary"
                                size="sm"
                                type="button"
                                onclick="openVendorSettleDebtModal({{ (int) $supplier->id }})"
                            >Settle debt</x-button>
                        @endif
                        @can('adjust-wallet-transactions')
                            <x-button variant="secondary" size="sm" type="button"
                                      data-open-opening-correction
                                      data-modal="supplier-opening-correction"
                                      data-action="{{ tenant_route('tenant.supplier-credit.vendors.opening-balance', ['supplier' => $supplier]) }}"
                                      data-entity="{{ $supplier->name }}"
                                      data-current="{{ format_money($supplier->opening_balance) }}"
                                      data-value="{{ number_format((float) $supplier->opening_balance, 2, '.', '') }}">Correct opening balance</x-button>
                        @endcan
                        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.vendors.edit', ['supplier' => $supplier]) }}">Edit</x-button>
                        @include('supplier-credit.partials.delete-button', [
                            'action' => tenant_route('tenant.supplier-credit.vendors.destroy', ['supplier' => $supplier]),
                            'confirm' => 'Delete this vendor?',
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

@include('supplier-credit.suppliers._settle-debt-modal', ['wallets' => $wallets ?? collect()])

@can('adjust-wallet-transactions')
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

@push('modals')
    <x-csv-import-modal
        modal-id="import-suppliers-modal"
        title="Import vendors (CSV)"
        :action="tenant_route('tenant.supplier-credit.suppliers.import.store')"
        :template-url="tenant_route('tenant.supplier-credit.suppliers.import.template')"
        columns-hint="Required columns: <span class=&quot;font-medium text-gray-800&quot;>name, phone, email, opening_balance</span>. Optional: contact_person, address, notes. Duplicates match on vendor name."
    />
@endpush

@push('scripts')
    @include('supplier-credit.suppliers._settle-debt-modal-script', ['settleDebtPayloads' => $settleDebtPayloads ?? []])
<script>
document.addEventListener('click', function (e) {
    if (e.target.closest('[data-open-import-suppliers]')) {
        e.preventDefault();
        window.openAppModal('import-suppliers-modal');
    }
});
@if(session('open_import_suppliers_modal') || $errors->has('csv_file'))
window.openAppModal('import-suppliers-modal');
@endif
</script>
@endpush
@endsection
