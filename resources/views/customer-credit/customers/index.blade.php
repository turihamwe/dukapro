@extends('layouts.admin')

@section('title', 'Credit Customers')

@section('content')
<x-page-header title="Customers" subtitle="Credit profiles, limits, and opening balances">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" type="button" data-open-import-customers>Import (CSV)</x-button>
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.customers.export') }}">Export (CSV)</x-button>
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.invoices.index') }}">Invoices</x-button>
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.payments.index') }}">Payments</x-button>
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

<p class="mb-4 text-xs text-gray-500">CSV export includes <strong>all credit customers</strong> for this business, not just the current page or search results.</p>

<form method="GET" action="{{ tenant_route('tenant.customer-credit.customers.index') }}" class="mb-4 flex flex-wrap items-center gap-2">
    <input type="search" name="q" value="{{ $search ?? '' }}" placeholder="Search name, phone…"
           class="min-w-[10rem] flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
    <select name="status" class="rounded-lg border border-gray-300 py-2 pl-2 pr-8 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="" @selected(empty($statusFilter))>All</option>
        <option value="active" @selected(($statusFilter ?? null) === 'active')>Active</option>
        <option value="inactive" @selected(($statusFilter ?? null) === 'inactive')>Inactive</option>
    </select>
    <select name="balance" class="rounded-lg border border-gray-300 py-2 pl-2 pr-8 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="" @selected(empty($balanceFilter))>Any balance</option>
        <option value="with_balance" @selected(($balanceFilter ?? null) === 'with_balance')>Owes</option>
        <option value="zero_balance" @selected(($balanceFilter ?? null) === 'zero_balance')>Zero</option>
    </select>
    <x-button variant="primary" size="sm" type="submit">Go</x-button>
    @if(($search ?? '') !== '' || ($statusFilter ?? null) || ($balanceFilter ?? null))
        <a href="{{ tenant_route('tenant.customer-credit.customers.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-800">Clear</a>
    @endif
</form>

<div class="space-y-3">
    @forelse($customers as $customer)
        <x-card :padding="false" class="p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="min-w-0">
                    <a href="{{ tenant_route('tenant.customer-credit.customers.show', ['customer' => $customer]) }}"
                       class="font-semibold text-gray-900 hover:text-indigo-700">{{ $customer->name }}</a>
                    @if($customer->company_name)
                        <p class="text-sm text-gray-500">{{ $customer->company_name }}</p>
                    @endif
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $customer->phone ?? '—' }}
                        · Outstanding @money($customer->outstanding_balance)
                        · Added {{ $customer->created_at->format('M j, Y') }}
                    </p>
                    @unless($customer->is_active)
                        <span class="mt-1 inline-flex rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">Inactive</span>
                    @endunless
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <x-button variant="success" size="sm" type="button" onclick="openAppModal('customer-payment-modal-{{ $customer->id }}')">Record payment</x-button>
                    <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.customers.edit', ['customer' => $customer]) }}">Edit</x-button>
                    @include('customer-credit.partials.delete-button', [
                        'action' => tenant_route('tenant.customer-credit.customers.destroy', ['customer' => $customer]),
                        'confirm' => 'Remove this customer and hide linked invoices and payments? Stock will NOT be restored.',
                    ])
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">
            @if(($search ?? '') !== '' || ($statusFilter ?? null) || ($balanceFilter ?? null))
                No customers match your search or filters.
                <a href="{{ tenant_route('tenant.customer-credit.customers.index') }}" class="font-medium text-indigo-600 hover:text-indigo-800">Clear filters</a>
            @else
                No credit customers yet. Add one below or create from POS checkout.
            @endif
        </x-card>
    @endforelse
</div>

<div class="mt-6">{{ $customers->links() }}</div>

<x-card class="mt-8">
    <h2 class="mb-3 text-sm font-semibold text-gray-900">Add customer</h2>
    <form method="POST" action="{{ tenant_route('tenant.customer-credit.customers.store') }}" class="space-y-4">
        @csrf
        @include('customer-credit.customers._form')
        <x-button variant="primary" type="submit">Save customer</x-button>
    </form>
</x-card>

@push('modals')
    <x-csv-import-modal
        modal-id="import-customers-modal"
        title="Import customers (CSV)"
        :action="tenant_route('tenant.customer-credit.customers.import.store')"
        :template-url="tenant_route('tenant.customer-credit.customers.import.template')"
        columns-hint="Required columns: <span class=&quot;font-medium text-gray-800&quot;>name, phone, email, address, opening_balance</span>. Optional: company_name, credit_limit, payment_terms_days, notes. Duplicates match on phone."
    />
    @foreach($customers as $customer)
        @include('customer-credit.customers._payment-modal', [
            'customer' => $customer,
            'wallets' => $wallets ?? collect(),
        ])
    @endforeach
@endpush

@push('scripts')
<script>
document.addEventListener('click', function (e) {
    if (e.target.closest('[data-open-import-customers]')) {
        e.preventDefault();
        window.openAppModal('import-customers-modal');
    }
});
@if(session('open_import_customers_modal') || $errors->has('csv_file'))
window.openAppModal('import-customers-modal');
@endif
@if($errors->any() && old('_payment_customer_id'))
(function () {
    var modal = document.getElementById('customer-payment-modal-{{ old('_payment_customer_id') }}');
    if (modal) window.openAppModal(modal);
})();
@endif
</script>
@endpush
@endsection
