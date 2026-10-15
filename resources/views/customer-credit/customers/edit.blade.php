@extends('layouts.admin')

@section('title', 'Edit ' . $customer->name)

@section('content')
<x-page-header :title="$customer->name" subtitle="Edit credit customer">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.customers.index') }}">All customers</x-button>
    </x-slot>
</x-page-header>

<x-card class="max-w-2xl">
    <form method="POST" action="{{ tenant_route('tenant.customer-credit.customers.update', ['customer' => $customer]) }}" class="space-y-4">
        @csrf
        @method('PUT')
        @include('customer-credit.customers._form', ['customer' => $customer])
        <div class="flex gap-2">
            <x-button variant="primary" type="submit">Save changes</x-button>
            <x-button variant="secondary" href="{{ tenant_route('tenant.customer-credit.customers.index') }}">Cancel</x-button>
        </div>
    </form>
</x-card>

@can('adjust-wallet-transactions')
    <div class="mt-4">
        <x-button variant="secondary" size="sm" type="button"
                  data-open-opening-correction
                  data-modal="customer-opening-correction"
                  data-action="{{ tenant_route('tenant.customer-credit.customers.opening-balance', ['customer' => $customer]) }}"
                  data-entity="{{ $customer->name }}"
                  data-current="{{ format_money($customer->opening_balance) }}"
                  data-value="{{ number_format((float) $customer->opening_balance, 2, '.', '') }}">Correct opening balance</x-button>
    </div>
    @push('modals')
        @include('partials.opening-correction-modal', [
            'modalId' => 'customer-opening-correction',
            'title' => 'Correct opening balance',
            'fieldName' => 'opening_balance',
            'fieldLabel' => 'New opening balance',
            'step' => '0.01',
            'currentLabel' => 'Current opening balance',
            'warning' => 'This updates only this customer\'s opening ledger entry, then recalculates that customer\'s outstanding balance. Other customers are not changed.',
        ])
    @endpush
@endcan
@endsection
