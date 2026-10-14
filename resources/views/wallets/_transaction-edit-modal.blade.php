@php
    $modalId = 'wallet-transaction-edit-modal';
    $categories = \App\Support\WalletInflowCategory::all();
@endphp
<div id="{{ $modalId }}" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="app-modal-panel max-w-lg">
        <div class="flex min-h-0 flex-1 flex-col">
            <div class="app-modal-header">
                <h2 id="{{ $modalId }}-title" class="text-lg font-semibold text-gray-900">Edit transaction</h2>
                <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('{{ $modalId }}')">&times;</button>
            </div>
            <div class="app-modal-body space-y-4">
                <p id="{{ $modalId }}-summary" class="text-sm text-gray-600">—</p>
                <p id="{{ $modalId }}-error" class="hidden text-sm text-red-600"></p>

                <x-input type="number" step="0.01" min="0.01" name="amount" id="{{ $modalId }}-amount" label="Amount" required />

                <div id="{{ $modalId }}-category-wrap">
                    <label for="{{ $modalId }}-category" class="mb-1 block text-sm font-medium text-gray-700">Category</label>
                    <select id="{{ $modalId }}-category" name="category" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach($categories as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <x-input type="text" name="reference" id="{{ $modalId }}-reference" label="Reference" maxlength="255" />
                <x-input type="text" name="notes" id="{{ $modalId }}-notes" label="Notes / description" maxlength="500" />
            </div>
            <div class="app-modal-footer">
                <x-button variant="secondary" type="button" onclick="closeAppModal('{{ $modalId }}')">Cancel</x-button>
                <x-button variant="primary" type="button" id="{{ $modalId }}-save">Save changes</x-button>
            </div>
        </div>
    </div>
</div>
