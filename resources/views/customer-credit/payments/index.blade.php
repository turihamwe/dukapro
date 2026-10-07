@extends('layouts.admin')

@section('title', 'Payments Received')

@section('content')
<x-page-header title="Payments Received" subtitle="Partial payments against customer balances">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.customer-credit.customers.index') }}">Customers</x-button>
    </x-slot>
</x-page-header>

<div class="space-y-3">
    @forelse($payments as $payment)
        <x-card :padding="false" class="p-4">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="font-semibold text-gray-900">@money($payment->amount)</p>
                    <p class="text-sm text-gray-600">
                        @if($payment->customer)
                            <a href="{{ tenant_route('tenant.customer-credit.customers.show', ['customer' => $payment->customer]) }}" class="text-indigo-600 hover:text-indigo-800">{{ $payment->customer->name }}</a>
                        @else
                            —
                        @endif
                        · {{ $payment->created_at->format('M d, Y H:i') }}
                    </p>
                    @if($payment->description)
                        <p class="mt-1 text-xs text-gray-500">{{ $payment->description }}</p>
                    @endif
                    @if($payment->paymentWallet)
                        <p class="mt-1 text-xs text-emerald-700">Deposited to {{ $payment->paymentWallet->name }}</p>
                    @endif
                </div>
                @include('customer-credit.partials.delete-button', [
                    'action' => tenant_route('tenant.customer-credit.payments.destroy', ['ledgerEntry' => $payment]),
                    'confirm' => 'Remove this payment record and update the customer balance?',
                ])
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No payments recorded yet.</x-card>
    @endforelse
</div>

<div class="mt-6">{{ $payments->links() }}</div>
@endsection
