@extends('layouts.admin')

@section('title', 'Credit Customers')

@section('content')
<x-page-header title="Customers" subtitle="Credit profiles, limits, and opening balances">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.invoices.index') }}">Invoices</x-button>
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.payments.index') }}">Payments</x-button>
    </x-slot>
</x-page-header>

<x-card class="mb-6">
    <h2 class="mb-3 text-sm font-semibold text-gray-900">Add customer</h2>
    <form method="POST" action="{{ tenant_route('tenant.customer-credit.customers.store') }}" class="space-y-4">
        @csrf
        @include('customer-credit.customers._form')
        <x-button variant="primary" type="submit">Save customer</x-button>
    </form>
</x-card>

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
                    <p class="mt-1 text-xs text-gray-500">{{ $customer->phone ?? '—' }} · Outstanding @money($customer->outstanding_balance)</p>
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
        <x-card class="text-center text-sm text-gray-500">No credit customers yet. Add one above or create from POS checkout.</x-card>
    @endforelse
</div>

<div class="mt-6">{{ $customers->links() }}</div>

@push('modals')
    @foreach($customers as $customer)
        @include('customer-credit.customers._payment-modal', [
            'customer' => $customer,
            'wallets' => $wallets ?? collect(),
        ])
    @endforeach
@endpush

@if($errors->any() && old('_payment_customer_id'))
@push('scripts')
<script>
(function () {
    var modal = document.getElementById('customer-payment-modal-{{ old('_payment_customer_id') }}');
    if (modal) window.openAppModal(modal);
})();
</script>
@endpush
@endif
@endsection
