@extends('layouts.admin')

@section('title', 'Bill')
@section('container_class', 'max-w-3xl')

@section('content')
<x-page-header title="Supplier bill" subtitle="{{ $purchase->supplier->name ?? 'Vendor' }} · {{ $purchase->purchase_date->format('M j, Y') }}">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.bills.index') }}">All bills</x-button>
        @if($balance > 0)
            <x-button variant="primary" size="sm" type="button" id="open-bill-payment-modal">Make payment</x-button>
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
    <ul class="mt-3 divide-y divide-gray-100">
        @foreach($purchase->lines as $line)
            <li class="flex justify-between gap-4 py-3 text-sm">
                <span class="text-gray-800">{{ $line->product ? $line->product->displayName() : 'Product' }}</span>
                <span class="shrink-0 text-gray-600">{{ $line->quantity }} × @money($line->unit_cost) = @money($line->line_total)</span>
            </li>
        @endforeach
    </ul>
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
                    </span>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>

@if($balance > 0)
@push('modals')
<div id="bill-payment-modal" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="bill-payment-title">
    <div class="app-modal-panel">
        <form method="POST" action="{{ tenant_route('tenant.supplier-credit.purchases.payments.store', ['purchase' => $purchase]) }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="app-modal-header">
                <h2 id="bill-payment-title" class="text-lg font-semibold text-gray-900">Make payment</h2>
                <button type="button" id="close-bill-payment-modal" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600">&times;</button>
            </div>
            <div class="app-modal-body space-y-4">
                <p class="text-sm text-gray-600">Remaining balance: <span class="font-semibold text-amber-800">@money($balance)</span></p>
                <x-input type="number" step="0.01" min="0.01" max="{{ $balance }}" name="amount" label="Amount" value="{{ old('amount') }}" required />
                <x-select name="payment_method" label="Method">
                    <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                    <option value="mobile_money" @selected(old('payment_method') === 'mobile_money')>Mobile Money</option>
                    <option value="bank" @selected(old('payment_method') === 'bank')>Bank</option>
                </x-select>
                <x-input type="text" name="reference" label="Reference (optional)" value="{{ old('reference') }}" />
                <x-input type="datetime-local" name="paid_at" label="Date & time (optional)" value="{{ old('paid_at') }}" />
                <x-textarea name="notes" label="Notes (optional)" rows="2">{{ old('notes') }}</x-textarea>
            </div>
            <div class="app-modal-footer">
                <x-button variant="secondary" type="button" id="cancel-bill-payment-modal">Cancel</x-button>
                <x-button variant="primary" type="submit">Record payment</x-button>
            </div>
        </form>
    </div>
</div>
@endpush

@push('scripts')
<script>
(function () {
    var modal = document.getElementById('bill-payment-modal');
    var openBtn = document.getElementById('open-bill-payment-modal');
    var closeBtn = document.getElementById('close-bill-payment-modal');
    var cancelBtn = document.getElementById('cancel-bill-payment-modal');

    if (openBtn && modal) openBtn.addEventListener('click', function () {
        window.openAppModal(modal);
    });
    if (closeBtn && modal) closeBtn.addEventListener('click', function () {
        window.closeAppModal(modal);
    });
    if (cancelBtn && modal) cancelBtn.addEventListener('click', function () {
        window.closeAppModal(modal);
    });

    @if($errors->any() && old('amount'))
        if (modal) window.openAppModal(modal);
    @endif
})();
</script>
@endpush
@endif
@endsection
