@php
    $modalId = $modalId ?? 'customer-payment-modal-' . $customer->id;
    $wallets = $wallets ?? collect();
    $isOld = (string) old('_payment_customer_id') === (string) $customer->id;
@endphp
<div id="{{ $modalId }}" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="app-modal-panel">
        <form method="POST" action="{{ tenant_route('tenant.customer-credit.customers.payments.store', ['customer' => $customer]) }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <input type="hidden" name="_payment_customer_id" value="{{ $customer->id }}">
            <div class="app-modal-header">
                <h2 id="{{ $modalId }}-title" class="text-lg font-semibold text-gray-900">Record payment received</h2>
                <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('{{ $modalId }}')">&times;</button>
            </div>
            <div class="app-modal-body space-y-4">
                <p class="text-sm text-gray-600">
                    {{ $customer->name }}
                    · Outstanding:
                    <span class="font-semibold text-amber-800">@money($customer->outstanding_balance)</span>
                </p>
                <x-input type="number" step="0.01" min="0.01" name="amount" label="Amount" value="{{ $isOld ? old('amount') : '' }}" required />
                @include('wallets._selector', ['wallets' => $wallets, 'label' => 'Deposit to account / wallet'])
                <x-input type="text" name="description" label="Note (optional)" value="{{ $isOld ? old('description') : '' }}" />
            </div>
            <div class="app-modal-footer">
                <x-button variant="secondary" type="button" onclick="closeAppModal('{{ $modalId }}')">Cancel</x-button>
                <x-button variant="success" type="submit">Record payment</x-button>
            </div>
        </form>
    </div>
</div>
