@if(!empty($canBranchTransfer) && ($transferBranches ?? collect())->isNotEmpty())
<div id="branch-transfer-modal" class="app-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="branch-transfer-title">
    <div class="app-modal-panel mx-auto w-full max-w-lg">
        <form method="POST" action="{{ tenant_route('tenant.inventory.branch-transfer.store') }}">
            @csrf
            <div class="app-modal-header">
                <h2 id="branch-transfer-title" class="text-lg font-semibold text-gray-900">Branch transfer</h2>
                <p class="mt-1 text-xs text-gray-500">Move stock between branches. Pricing and cost records are not changed.</p>
            </div>
            <div class="app-modal-body space-y-4">
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="transfer-from-branch" class="mb-1 block text-sm font-medium text-gray-700">From branch</label>
                        <select id="transfer-from-branch" name="from_branch_id" required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
                                @if(auth()->user()->branch_id) disabled @endif>
                            @foreach($transferBranches as $id => $name)
                                <option value="{{ $id }}" @selected((int) auth()->user()->branch_id === (int) $id || (! auth()->user()->branch_id && $loop->first))>{{ $name }}</option>
                            @endforeach
                        </select>
                        @if(auth()->user()->branch_id)
                            <input type="hidden" name="from_branch_id" value="{{ auth()->user()->branch_id }}">
                        @endif
                    </div>
                    <div>
                        <label for="transfer-to-branch" class="mb-1 block text-sm font-medium text-gray-700">To branch</label>
                        <select id="transfer-to-branch" name="to_branch_id" required
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="">Select destination…</option>
                            @foreach($transferBranches as $id => $name)
                                <option value="{{ $id }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label for="transfer-product" class="mb-1 block text-sm font-medium text-gray-700">Product</label>
                    <select id="transfer-product" name="product_id" required disabled
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm disabled:bg-gray-50">
                        <option value="">Loading products…</option>
                    </select>
                </div>

                <div id="transfer-variant-wrap" class="hidden">
                    <label for="transfer-variant" class="mb-1 block text-sm font-medium text-gray-700">Variant</label>
                    <select id="transfer-variant" name="variant_id"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                        <option value="">Select variant…</option>
                    </select>
                </div>

                <div>
                    <label for="transfer-quantity" class="mb-1 block text-sm font-medium text-gray-700">Quantity to move</label>
                    <input type="number" id="transfer-quantity" name="quantity" step="any" min="0.001" required
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <p id="transfer-available-hint" class="mt-1 text-xs text-gray-500"></p>
                </div>
            </div>
            <div class="app-modal-footer">
                <button type="button" class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        onclick="closeAppModal('branch-transfer-modal')">Cancel</button>
                <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    Transfer stock
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var productsUrl = @json(tenant_route('tenant.inventory.branch-transfer.products'));
    var fromSelect = document.getElementById('transfer-from-branch');
    var toSelect = document.getElementById('transfer-to-branch');
    var productSelect = document.getElementById('transfer-product');
    var variantWrap = document.getElementById('transfer-variant-wrap');
    var variantSelect = document.getElementById('transfer-variant');
    var qtyInput = document.getElementById('transfer-quantity');
    var availableHint = document.getElementById('transfer-available-hint');
    var catalog = [];

    function syncToBranchOptions() {
        if (!fromSelect || !toSelect) return;
        var fromId = String(fromSelect.value || '');
        Array.prototype.forEach.call(toSelect.options, function (opt) {
            if (!opt.value) return;
            opt.hidden = opt.value === fromId;
            if (opt.value === fromId && toSelect.value === fromId) {
                toSelect.value = '';
            }
        });
    }

    function selectedProduct() {
        var id = productSelect && productSelect.value ? parseInt(productSelect.value, 10) : null;
        if (!id) return null;
        return catalog.find(function (p) { return p.id === id; }) || null;
    }

    function updateAvailableHint() {
        if (!availableHint) return;
        var product = selectedProduct();
        if (!product) {
            availableHint.textContent = '';
            return;
        }
        if (product.is_variable) {
            var variantId = variantSelect && variantSelect.value ? parseInt(variantSelect.value, 10) : null;
            var variant = product.variants.find(function (v) { return v.id === variantId; });
            availableHint.textContent = variant
                ? 'Available at source: ' + variant.available
                : 'Select a variant to see available stock.';
            return;
        }
        availableHint.textContent = 'Available at source: ' + product.available;
    }

    function fillVariants(product) {
        if (!variantWrap || !variantSelect) return;
        variantSelect.innerHTML = '<option value="">Select variant…</option>';
        if (!product || !product.is_variable) {
            variantWrap.classList.add('hidden');
            variantSelect.removeAttribute('required');
            updateAvailableHint();
            return;
        }
        variantWrap.classList.remove('hidden');
        variantSelect.setAttribute('required', 'required');
        product.variants.forEach(function (v) {
            var opt = document.createElement('option');
            opt.value = v.id;
            opt.textContent = v.label + ' (' + v.available + ' available)';
            variantSelect.appendChild(opt);
        });
        updateAvailableHint();
    }

    function fillProducts(items) {
        catalog = items || [];
        if (!productSelect) return;
        productSelect.innerHTML = '<option value="">Select product…</option>';
        catalog.forEach(function (p) {
            var opt = document.createElement('option');
            opt.value = p.id;
            if (p.is_variable) {
                opt.textContent = p.name + ' (variants)';
            } else {
                opt.textContent = p.name + ' (' + p.available + ' available)';
            }
            productSelect.appendChild(opt);
        });
        productSelect.disabled = catalog.length === 0;
        if (catalog.length === 0) {
            productSelect.innerHTML = '<option value="">No products at this branch</option>';
        }
        fillVariants(null);
    }

    function loadProducts() {
        if (!fromSelect || !productSelect) return;
        var fromId = fromSelect.value;
        if (!fromId) return;
        productSelect.disabled = true;
        productSelect.innerHTML = '<option value="">Loading…</option>';
        fetch(productsUrl + '?from_branch_id=' + encodeURIComponent(fromId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) { return res.ok ? res.json() : Promise.reject(); })
            .then(fillProducts)
            .catch(function () {
                productSelect.innerHTML = '<option value="">Could not load products</option>';
            });
    }

    if (fromSelect) {
        fromSelect.addEventListener('change', function () {
            syncToBranchOptions();
            loadProducts();
        });
    }
    if (productSelect) {
        productSelect.addEventListener('change', function () {
            fillVariants(selectedProduct());
        });
    }
    if (variantSelect) {
        variantSelect.addEventListener('change', updateAvailableHint);
    }

    document.addEventListener('click', function (e) {
        var opener = e.target.closest('[data-open-branch-transfer]');
        if (!opener) return;
        syncToBranchOptions();
        loadProducts();
        openAppModal('branch-transfer-modal');
    });

    syncToBranchOptions();
})();
</script>
@endpush
@endif
