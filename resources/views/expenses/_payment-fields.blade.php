@php
    $wallets = $wallets ?? collect();
    $usesPaymentWallets = $usesPaymentWallets ?? false;
    $selectedWalletId = old('payment_wallet_id', $selectedWalletId ?? null);
@endphp
<div class="grid gap-4 sm:grid-cols-2">
    @if($usesPaymentWallets && $wallets->isNotEmpty())
        <div class="sm:col-span-2">
            @include('wallets._selector', [
                'wallets' => $wallets,
                'label' => 'Paid from account / wallet',
                'required' => true,
                'name' => 'payment_wallet_id',
                'selected' => $selectedWalletId,
            ])
            @error('payment_wallet_id')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    @else
        <div>
            <label class="mb-1 block text-sm font-medium text-gray-700">Payment method</label>
            <select name="payment_method" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                <option value="cash" @selected(old('payment_method', $paymentMethod ?? 'cash') === 'cash')>Cash</option>
                <option value="mobile_money" @selected(old('payment_method', $paymentMethod ?? '') === 'mobile_money')>Mobile Money</option>
                <option value="bank" @selected(old('payment_method', $paymentMethod ?? '') === 'bank')>Bank</option>
            </select>
        </div>
    @endif
    <x-input type="text" name="receipt_reference" label="Receipt / reference" value="{{ old('receipt_reference', $receiptReference ?? '') }}" />
</div>
