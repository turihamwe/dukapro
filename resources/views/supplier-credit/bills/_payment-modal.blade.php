@php
    $modalId = $modalId ?? 'bill-payment-modal-' . $purchase->id;
    $balance = isset($balance) ? $balance : $purchase->balanceDue();
    $wallets = $wallets ?? collect();
@endphp
@if($balance > 0)
<div id="{{ $modalId }}" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="app-modal-panel">
        <form method="POST" action="{{ tenant_route('tenant.supplier-credit.purchases.payments.store', ['purchase' => $purchase]) }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <input type="hidden" name="_payment_purchase_id" value="{{ $purchase->id }}">
            <div class="app-modal-header">
                <h2 id="{{ $modalId }}-title" class="text-lg font-semibold text-gray-900">Make payment</h2>
                <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('{{ $modalId }}')">&times;</button>
            </div>
            <div class="app-modal-body space-y-4">
                <p class="text-sm text-gray-600">
                    {{ $purchase->supplier->name ?? 'Vendor' }}
                    · Remaining balance:
                    <span class="font-semibold text-amber-800">@money($balance)</span>
                </p>
                <x-input type="number" step="0.01" min="0.01" max="{{ $balance }}" name="amount" label="Amount" value="{{ old('_payment_purchase_id') == $purchase->id ? old('amount') : '' }}" required />
                @include('wallets._selector', ['wallets' => $wallets, 'label' => 'Paid from account / wallet'])
                <x-select name="payment_method" label="Method">
                    <option value="cash" @selected(old('_payment_purchase_id') == $purchase->id && old('payment_method', 'cash') === 'cash')>Cash</option>
                    <option value="mobile_money" @selected(old('_payment_purchase_id') == $purchase->id && old('payment_method') === 'mobile_money')>Mobile Money</option>
                    <option value="bank" @selected(old('_payment_purchase_id') == $purchase->id && old('payment_method') === 'bank')>Bank</option>
                </x-select>
                <x-input type="text" name="reference" label="Reference (optional)" value="{{ old('_payment_purchase_id') == $purchase->id ? old('reference') : '' }}" />
                <x-input type="datetime-local" name="paid_at" label="Date & time (optional)" value="{{ old('_payment_purchase_id') == $purchase->id ? old('paid_at') : '' }}" />
                <x-textarea name="notes" label="Notes (optional)" rows="2">{{ old('_payment_purchase_id') == $purchase->id ? old('notes') : '' }}</x-textarea>
            </div>
            <div class="app-modal-footer">
                <x-button variant="secondary" type="button" onclick="closeAppModal('{{ $modalId }}')">Cancel</x-button>
                <x-button variant="primary" type="submit">Record payment</x-button>
            </div>
        </form>
    </div>
</div>
@endif
