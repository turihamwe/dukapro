@php
    $modalId = 'wallet-deposit-modal';
    $wallets = $wallets->where('is_active', true);
    $inflowCategories = $inflowCategories ?? \App\Support\WalletInflowCategory::all();
@endphp
<div id="{{ $modalId }}" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="app-modal-panel max-w-lg">
        <form method="POST" action="{{ tenant_route('tenant.wallets.deposit') }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="app-modal-header">
                <h2 id="{{ $modalId }}-title" class="text-lg font-semibold text-gray-900">Add funds</h2>
                <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('{{ $modalId }}')">&times;</button>
            </div>
            <div class="app-modal-body space-y-4">
                <x-input type="number" step="0.01" min="0.01" name="amount" label="Amount" value="{{ old('amount') }}" required />

                <x-select name="payment_wallet_id" label="Target wallet" required>
                    <option value="">Select wallet…</option>
                    @foreach($wallets as $wallet)
                        <option value="{{ $wallet->id }}" @selected((string) old('payment_wallet_id') === (string) $wallet->id)>
                            {{ $wallet->name }} ({{ $wallet->typeLabel() }}) · @money($wallet->current_balance)
                        </option>
                    @endforeach
                </x-select>

                <x-select name="category" label="Inflow category" required>
                    @foreach($inflowCategories as $value => $label)
                        <option value="{{ $value }}" @selected(old('category', 'miscellaneous') === $value)>{{ $label }}</option>
                    @endforeach
                </x-select>

                <x-input type="text" name="source_name" label="Source name / note" placeholder="e.g. Personal injection from Director" value="{{ old('source_name') }}" maxlength="500" />

                <x-input type="datetime-local" name="occurred_at" label="Date & time (optional)" value="{{ old('occurred_at') }}" />
            </div>
            <div class="app-modal-footer">
                <x-button variant="secondary" type="button" onclick="closeAppModal('{{ $modalId }}')">Cancel</x-button>
                <x-button variant="primary" type="submit">Record deposit</x-button>
            </div>
        </form>
    </div>
</div>
