@extends('layouts.admin')

@section('title', 'Bill')
@section('container_class', 'max-w-3xl')

@section('content')
<x-page-header title="Supplier bill" subtitle="{{ $purchase->supplier->name ?? 'Vendor' }} · {{ $purchase->purchase_date->format('M j, Y') }}">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.bills.index') }}">All bills</x-button>
        @if($balance > 0)
            <x-button variant="primary" size="sm" type="button" onclick="openAppModal('bill-payment-modal-{{ $purchase->id }}')">Make payment</x-button>
        @endif
    </x-slot>
</x-page-header>

<x-card class="mb-6 !p-5">
    <div class="flex flex-wrap items-center gap-2">
        <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize
            @if($purchase->status === 'paid') bg-emerald-100 text-emerald-800
            @elseif($purchase->status === 'partial') bg-amber-100 text-amber-900
            @else bg-gray-100 text-gray-700 @endif">{{ $purchase->status }}</span>
        @if($purchase->reference)
            <span class="text-xs text-gray-500">Ref: {{ $purchase->reference }}</span>
        @endif
    </div>
    <dl class="mt-4 grid gap-4 sm:grid-cols-3">
        <div>
            <dt class="text-xs text-gray-500">Bill total</dt>
            <dd class="text-lg font-semibold text-gray-900">@money($purchase->total_amount)</dd>
        </div>
        <div>
            <dt class="text-xs text-gray-500">Paid so far</dt>
            <dd class="text-lg font-semibold text-emerald-700">@money($purchase->amount_paid)</dd>
        </div>
        <div>
            <dt class="text-xs text-gray-500">Remaining balance</dt>
            <dd class="text-lg font-bold text-amber-800">@money($balance)</dd>
        </div>
    </dl>
    @if($purchase->notes)
        <p class="mt-4 text-sm text-gray-600">{{ $purchase->notes }}</p>
    @endif
</x-card>

<x-card class="mb-6 !p-5">
    <h2 class="text-sm font-semibold text-gray-900">Line items</h2>
    @if($purchase->is_opening_balance && $purchase->lines->isEmpty())
        <p class="mt-3 text-sm text-gray-600">
            This bill records historical supplier debt only. No products or stock were added to inventory.
        </p>
    @elseif($purchase->lines->isEmpty())
        <p class="mt-3 text-sm text-gray-500">No line items.</p>
    @else
        <ul class="mt-3 divide-y divide-gray-100">
            @foreach($purchase->lines as $line)
                <li class="flex justify-between gap-4 py-3 text-sm">
                    <span class="text-gray-800">{{ $line->product ? $line->product->displayName() : 'Product' }}</span>
                    <span class="shrink-0 text-gray-600">{{ $line->quantity }} × @money($line->unit_cost) = @money($line->line_total)</span>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>

<x-card class="!p-5">
    <h2 class="text-sm font-semibold text-gray-900">Payments made</h2>
    @if($purchase->payments->isEmpty())
        <p class="mt-3 text-sm text-gray-500">No payments recorded yet.</p>
    @else
        <ul class="mt-3 space-y-2">
            @foreach($purchase->payments as $payment)
                <li class="flex flex-wrap items-baseline justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2 text-sm">
                    <span class="font-medium text-gray-900">@money($payment->amount)</span>
                    <span class="text-gray-600">
                        {{ $payment->paid_at->format('M j, Y g:i A') }}
                        @if($payment->payment_method) · {{ $payment->payment_method }} @endif
                        @if($payment->reference) · {{ $payment->reference }} @endif
                        @if($payment->paymentWallet) · {{ $payment->paymentWallet->name }} @endif
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>

@if($balance > 0)
@push('modals')
    @include('supplier-credit.bills._payment-modal', [
        'purchase' => $purchase,
        'balance' => $balance,
        'wallets' => $wallets ?? collect(),
    ])
@endpush

@push('scripts')
<script>
(function () {
    @if($errors->any() && (string) old('_payment_purchase_id') === (string) $purchase->id)
        var modal = document.getElementById('bill-payment-modal-{{ $purchase->id }}');
        if (modal) window.openAppModal(modal);
    @endif
})();
</script>
@endpush
@endif
@endsection
