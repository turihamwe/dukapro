@php
    $modalId = 'wallet-adjust-modal';
    $activeWallets = $wallets->where('is_active', true);
@endphp
<div id="{{ $modalId }}" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="app-modal-panel max-w-lg">
        <form method="POST" action="{{ tenant_route('tenant.wallets.adjust') }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <input type="hidden" name="payment_wallet_id" id="{{ $modalId }}-wallet-id" value="{{ old('payment_wallet_id') }}">
            <div class="app-modal-header">
                <div>
                    <h2 id="{{ $modalId }}-title" class="text-lg font-semibold text-gray-900">Adjust cash</h2>
                    <p id="{{ $modalId }}-wallet-label" class="mt-0.5 text-sm text-gray-500">Select a wallet</p>
                </div>
                <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('{{ $modalId }}')">&times;</button>
            </div>
            <div class="app-modal-body space-y-4">
                <fieldset>
                    <legend class="mb-2 block text-sm font-medium text-gray-700">Adjustment type</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="flex cursor-pointer items-center justify-center rounded-lg border px-3 py-2.5 text-sm font-medium transition has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-800 border-gray-200 text-gray-700">
                            <input type="radio" name="adjustment_type" value="add" class="sr-only" @checked(old('adjustment_type', 'add') === 'add')>
                            + Add cash
                        </label>
                        <label class="flex cursor-pointer items-center justify-center rounded-lg border px-3 py-2.5 text-sm font-medium transition has-[:checked]:border-red-500 has-[:checked]:bg-red-50 has-[:checked]:text-red-800 border-gray-200 text-gray-700">
                            <input type="radio" name="adjustment_type" value="reduce" class="sr-only" @checked(old('adjustment_type') === 'reduce')>
                            − Reduce cash
                        </label>
                    </div>
                    @error('adjustment_type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </fieldset>

                <x-input type="number" step="0.01" min="0.01" name="amount" label="Amount" value="{{ old('amount') }}" required />
                @error('amount')
                    <p class="-mt-2 text-xs text-red-600">{{ $message }}</p>
                @enderror

                <div>
                    <label for="{{ $modalId }}-reason" class="mb-1 block text-sm font-medium text-gray-700">Reason / description <span class="text-red-600">*</span></label>
                    <textarea name="reason" id="{{ $modalId }}-reason" rows="3" required maxlength="2000" placeholder="e.g. Drawer count correction, unrecorded petty cash"
                              class="block w-full rounded-lg border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('reason') }}</textarea>
                    @error('reason')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <p class="text-xs text-gray-500">Adjustments are logged with your user account and appear in transaction history.</p>
            </div>
            <div class="app-modal-footer">
                <x-button variant="secondary" type="button" onclick="closeAppModal('{{ $modalId }}')">Cancel</x-button>
                <x-button variant="primary" type="submit">Save adjustment</x-button>
            </div>
        </form>
    </div>
</div>
<script type="application/json" id="{{ $modalId }}-wallets-json">@json($activeWallets->map(function ($w) {
    return [
        'id' => $w->id,
        'name' => $w->name,
        'type_label' => $w->typeLabel(),
        'balance' => (float) $w->current_balance,
    ];
})->values())</script>
