@extends('layouts.admin')

@section('title', 'Payments Made')
@section('container_class', 'max-w-4xl')

@section('content')
<x-page-header title="Payments made" subtitle="Chronological history of supplier payments">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.bills.index') }}">Bills</x-button>
    </x-slot>
</x-page-header>

<div class="space-y-3">
    @forelse($payments as $payment)
        <x-card class="!p-4">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-sm font-semibold text-gray-900">{{ $payment->supplier->name ?? 'Vendor' }}</p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $payment->paid_at->format('M j, Y g:i A') }}
                        @if($payment->user) · {{ $payment->user->name }} @endif
                    </p>
                    @if($payment->payment_method || $payment->reference)
                        <p class="mt-1 text-xs text-gray-500">
                            @if($payment->payment_method){{ $payment->payment_method }}@endif
                            @if($payment->reference) · {{ $payment->reference }}@endif
                        </p>
                    @endif
                </div>
                <div class="flex flex-col items-end gap-2 text-right">
                    <p class="text-lg font-bold text-emerald-700">@money($payment->amount)</p>
                    <div class="flex flex-wrap items-center justify-end gap-2">
                        @if($payment->purchase)
                            <a href="{{ tenant_route('tenant.supplier-credit.bills.show', ['purchase' => $payment->purchase]) }}"
                               class="text-xs font-medium text-indigo-600 hover:text-indigo-800">View</a>
                        @endif
                        @include('supplier-credit.partials.delete-button', [
                            'action' => tenant_route('tenant.supplier-credit.payments.destroy', ['payment' => $payment]),
                            'confirm' => 'Are you sure you want to delete this payment? The bill balance will be recalculated.',
                            'label' => 'Delete',
                        ])
                    </div>
                </div>
            </div>
        </x-card>
    @empty
        <x-card class="text-center text-sm text-gray-500">No supplier payments recorded yet.</x-card>
    @endforelse
</div>

<div class="mt-6">{{ $payments->links() }}</div>
@endsection
