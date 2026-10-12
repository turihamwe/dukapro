@php
    $modalId = 'add-vendor-bill-modal';
    $suppliers = $suppliers ?? collect();
@endphp
<div id="{{ $modalId }}" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="app-modal-panel max-w-lg">
        <form method="POST" action="{{ tenant_route('tenant.supplier-credit.bills.manual.store') }}" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="app-modal-header">
                <h2 id="{{ $modalId }}-title" class="text-lg font-semibold text-gray-900">Add vendor bill</h2>
                <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('{{ $modalId }}')">&times;</button>
            </div>
            <div class="app-modal-body space-y-4">
                <p class="text-xs text-gray-500">Record a supplier invoice on credit without receiving stock. Payables increase immediately.</p>

                <x-select name="supplier_id" label="Vendor" required>
                    <option value="">Select vendor…</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" @selected(old('supplier_id') == $supplier->id)>{{ $supplier->name }}</option>
                    @endforeach
                </x-select>

                <x-input type="number" step="0.01" min="0.01" name="amount" label="Bill amount" value="{{ old('amount') }}" required />

                <x-input type="date" name="purchase_date" label="Bill / invoice date" value="{{ old('purchase_date', now()->toDateString()) }}" required max="{{ now()->toDateString() }}" />

                <x-input type="text" name="reference" label="Invoice / reference (optional)" value="{{ old('reference') }}" maxlength="100" />

                <x-textarea name="source_note" label="Description / note (optional)" rows="2">{{ old('source_note') }}</x-textarea>

                <label class="flex cursor-pointer items-start gap-3 rounded-lg border border-gray-200 bg-gray-50 p-3 text-sm">
                    <input type="checkbox" name="is_historical_adjustment" value="1" class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" @checked(old('is_historical_adjustment'))>
                    <span>
                        <span class="font-medium text-gray-900">Historical / adjustment bill</span>
                        <span class="mt-0.5 block text-xs text-gray-500">Use for past-dated invoices or corrections. No inventory is added.</span>
                    </span>
                </label>
            </div>
            <div class="app-modal-footer">
                <x-button variant="secondary" type="button" onclick="closeAppModal('{{ $modalId }}')">Cancel</x-button>
                <x-button variant="primary" type="submit">Save bill</x-button>
            </div>
        </form>
    </div>
</div>
