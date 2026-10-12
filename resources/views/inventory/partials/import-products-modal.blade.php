@php
    $modalId = 'import-products-modal';
    $importBranches = $importBranches ?? collect();
@endphp
<div id="{{ $modalId }}" class="app-modal-overlay" aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="{{ $modalId }}-title">
    <div class="app-modal-panel max-w-lg">
        <form method="POST" action="{{ tenant_route('tenant.inventory.import.store') }}" enctype="multipart/form-data" class="flex min-h-0 flex-1 flex-col">
            @csrf
            <div class="app-modal-header">
                <h2 id="{{ $modalId }}-title" class="text-lg font-semibold text-gray-900">Import products (CSV)</h2>
                <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('{{ $modalId }}')">&times;</button>
            </div>
            <div class="app-modal-body space-y-4">
                <p class="text-sm text-gray-600">
                    Upload a CSV with columns:
                    <span class="font-medium text-gray-800">name, sku, category, cost_price, selling_price, initial_stock</span>.
                    Optional column <span class="font-medium">branch</span> overrides the branch below.
                </p>
                <p class="text-sm">
                    <a href="{{ tenant_route('tenant.inventory.import.template') }}" class="font-medium text-emerald-700 hover:text-emerald-800 underline">Download sample template</a>
                </p>

                @if(auth()->user()->isOwner() && $importBranches->isNotEmpty())
                    <label for="import-branch_id" class="mb-1 block text-sm font-medium text-gray-700">Default branch</label>
                    <p class="mb-2 text-xs text-gray-500">Required unless every row includes a <span class="font-medium">branch</span> column.</p>
                    <select name="branch_id" id="import-branch_id" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="">Select branch…</option>
                        @foreach($importBranches as $id => $name)
                            <option value="{{ $id }}" @selected((int) old('branch_id', $branchId ?? 0) === (int) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                    @error('branch_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                @endif

                <div>
                    <label for="csv_file" class="mb-1 block text-sm font-medium text-gray-700">CSV file</label>
                    <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv,text/plain" required
                           class="block w-full text-sm text-gray-700 file:mr-3 file:rounded-lg file:border-0 file:bg-emerald-50 file:px-3 file:py-2 file:text-sm file:font-medium file:text-emerald-800 hover:file:bg-emerald-100">
                    @error('csv_file')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="app-modal-footer">
                <x-button variant="secondary" type="button" onclick="closeAppModal('{{ $modalId }}')">Cancel</x-button>
                <x-button variant="primary" type="submit">Import</x-button>
            </div>
        </form>
    </div>
</div>
