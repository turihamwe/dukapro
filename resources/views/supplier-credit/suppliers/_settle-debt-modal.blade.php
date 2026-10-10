@php
    $wallets = $wallets ?? collect();
    $modalId = 'vendor-settle-debt-modal';
@endphp
<div id="{{ $modalId }}" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="app-modal-panel max-w-lg">
        <form method="POST" action="#" id="vendor-settle-debt-form" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="app-modal-header">
                <h2 id="{{ $modalId }}-title" class="text-lg font-semibold text-gray-900">Settle vendor debt</h2>
                <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('{{ $modalId }}')">&times;</button>
            </div>
            <div class="app-modal-body space-y-4">
                <p class="text-sm text-gray-600">
                    <span id="vendor-settle-debt-name" class="font-semibold text-gray-900"></span>
                    · Total outstanding:
                    <span id="vendor-settle-debt-balance" class="font-semibold text-amber-800"></span>
                </p>
                <p class="text-xs text-gray-500">Payments apply to open bills oldest first (FIFO).</p>

                <x-input type="number" step="0.01" min="0.01" name="amount" id="vendor-settle-debt-amount" label="Payment amount" required />

                @include('wallets._selector', ['wallets' => $wallets, 'label' => 'Paid from wallet'])

                <x-select name="payment_method" label="Method">
                    <option value="cash">Cash</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="bank">Bank</option>
                </x-select>

                <x-input type="date" name="paid_at" id="vendor-settle-debt-date" label="Payment date" value="{{ now()->toDateString() }}" />

                <x-input type="text" name="reference" label="Reference (optional)" />

                <x-textarea name="notes" label="Notes (optional)" rows="2"></x-textarea>

                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-gray-500">FIFO allocation preview</p>
                    <div id="vendor-settle-debt-preview" class="mt-2 max-h-48 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 text-sm"></div>
                    <p id="vendor-settle-debt-preview-empty" class="mt-2 hidden text-xs text-gray-500">Enter an amount to see how it will be applied.</p>
                </div>
            </div>
            <div class="app-modal-footer">
                <x-button variant="secondary" type="button" onclick="closeAppModal('{{ $modalId }}')">Cancel</x-button>
                <x-button variant="primary" type="submit">Record payment</x-button>
            </div>
        </form>
    </div>
</div>
