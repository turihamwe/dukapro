@extends('layouts.admin')

@section('title', 'Purchase Receives')
@section('container_class', 'max-w-6xl')

@section('content')
<x-page-header title="Purchase receives / restock" subtitle="One supplier delivery is saved as a single bill, with a line for each item">
    <x-slot name="actions">
        <x-button variant="secondary" size="sm" href="{{ tenant_route('tenant.supplier-credit.bills.index') }}">Bills</x-button>
    </x-slot>
</x-page-header>

@if($errors->any())
    <x-card class="mb-4 border-red-200 bg-red-50 text-sm text-red-800">
        <ul class="list-disc space-y-1 pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-card>
@endif

<form method="POST"
      action="{{ tenant_route('tenant.supplier-credit.purchases.store') }}"
      x-data="purchaseReceive()"
      x-on:submit="onSubmit($event)"
      class="space-y-4">
    @csrf

    <x-card class="!p-4 sm:!p-5">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="sm:col-span-2">
                @include('supplier-credit.purchases._supplier-field')
            </div>
            <div>
                <x-input type="date" name="purchase_date" label="Bill date" value="{{ old('purchase_date', now()->toDateString()) }}" />
            </div>
            <div>
                <x-input type="text" name="reference" label="Bill number" value="{{ old('reference') }}" placeholder="Leave blank to assign one" maxlength="100" />
            </div>
            <div class="sm:col-span-2 lg:col-span-4">
                <label for="bill-notes" class="mb-1.5 block text-sm font-medium text-gray-700">Notes</label>
                <textarea id="bill-notes" name="notes" rows="2" maxlength="2000" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
            </div>
        </div>
    </x-card>

    <x-card class="!p-4 sm:!p-5">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">Line items</h2>
                <p class="mt-0.5 text-xs text-gray-500">CSV columns: sku_or_barcode, quantity, unit_cost</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <input type="file" accept=".csv,text/csv" class="hidden" x-ref="csv" x-on:change="importCsv($event)">
                <x-button variant="secondary" size="sm" type="button" x-on:click="$refs.csv.click()">Upload CSV</x-button>
                <x-button variant="secondary" size="sm" type="button" x-on:click="addLine()">Add line</x-button>
            </div>
        </div>

        <p x-show="formError" x-cloak class="mt-3 text-sm text-red-600" x-text="formError"></p>
        <ul x-show="csvErrors.length" x-cloak class="mt-3 list-disc space-y-1 pl-5 text-sm text-red-600">
            <template x-for="message in csvErrors" :key="message">
                <li x-text="message"></li>
            </template>
        </ul>

        <div class="mt-4 space-y-4">
            <template x-for="(line, index) in lines" :key="index">
                <div class="grid gap-3 border-b border-gray-100 pb-4 sm:grid-cols-2 lg:grid-cols-12 lg:items-start">
                    <div class="relative sm:col-span-2 lg:col-span-5">
                        <label class="mb-1 block text-xs font-medium text-gray-600">Product</label>
                        <input type="hidden" :name="'lines[' + index + '][product_id]'" :value="line.product_id">
                        <input type="text"
                               x-model="line.query"
                               x-on:focus="line.open = true"
                               x-on:input="line.product_id = ''; line.previous_cost = null; line.open = true"
                               placeholder="Search name or SKU"
                               autocomplete="off"
                               class="block w-full rounded-lg border-gray-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <div x-show="line.open" x-cloak x-on:click.outside="line.open = false" class="absolute z-20 mt-1 max-h-56 w-full overflow-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                            <template x-for="product in matches(line)" :key="product.id">
                                <button type="button" class="block w-full px-3 py-2 text-left hover:bg-gray-50" x-on:click="choose(line, product)">
                                    <span class="block font-medium text-gray-900" x-text="product.name"></span>
                                    <span class="block text-xs text-gray-500" x-text="product.sku || 'No SKU'"></span>
                                </button>
                            </template>
                            <p x-show="matches(line).length === 0" class="px-3 py-2 text-xs text-gray-500">No matching products.</p>
                        </div>
                        <p class="mt-1 text-xs text-gray-500" x-show="line.sku" x-text="'SKU ' + line.sku"></p>
                    </div>
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-gray-600">Quantity</label>
                        <input type="number" step="0.001" min="0.001" required
                               :name="'lines[' + index + '][quantity]'"
                               x-model="line.quantity"
                               class="block w-full rounded-lg border-gray-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="lg:col-span-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600">Unit cost</label>
                        <input type="number" step="0.01" min="0" required
                               :name="'lines[' + index + '][unit_cost]'"
                               x-model="line.unit_cost"
                               class="block w-full rounded-lg border-gray-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="flex items-end justify-between gap-3 lg:col-span-2 lg:flex-col lg:items-end">
                        <div class="lg:text-right">
                            <p class="mb-1 text-xs font-medium text-gray-600">Subtotal</p>
                            <p class="py-2 text-sm font-semibold text-gray-900" x-text="money(subtotal(line))"></p>
                        </div>
                        <button type="button" class="pb-2 text-xs font-medium text-red-600 hover:text-red-800" x-on:click="removeLine(index)">Remove</button>
                    </div>
                    <p class="text-xs text-gray-500 sm:col-span-2 lg:col-span-12" x-show="canViewCosts && line.product_id && line.previous_cost == null" x-cloak>No previous price</p>
                    <p class="text-xs text-gray-500 sm:col-span-2 lg:col-span-12" x-show="canViewCosts && line.product_id && line.previous_cost != null" x-cloak>
                        Previously:
                        <span class="font-medium text-gray-700" x-text="money(line.previous_cost)"></span>
                        / <span x-text="line.unit_label || 'unit'"></span>
                        <span x-show="line.previous_change != null" class="font-medium" :class="changeClass(line)" x-text="'(' + changeLabel(line) + ')'"></span>
                    </p>
                </div>
            </template>
        </div>

        <div class="mt-4 flex flex-col gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <p class="text-sm text-gray-600"><span class="font-semibold text-gray-900" x-text="filledCount()"></span> items on this bill</p>
            <p class="text-base font-semibold text-gray-900">Grand total <span x-text="money(grandTotal())"></span></p>
        </div>
    </x-card>

    <div class="flex justify-end">
        <x-button variant="primary" type="submit">Save bill</x-button>
    </div>
</form>
@endsection

<script>
document.addEventListener('alpine:init', function () {
    Alpine.data('purchaseReceive', function () {
        return {
            catalog: @json($catalog),
            currencySample: @json($currencySample),
            canViewCosts: @json(auth()->user()->can('view-cost-prices')),
            lines: @json($initialLines),
            csvErrors: [],
            formError: '',
            submitting: false,
            addLine: function () {
                this.lines.push({ product_id: '', query: '', name: '', sku: '', quantity: '', unit_cost: '', previous_cost: null, previous_change: null, unit_label: 'unit', open: false });
            },
            removeLine: function (index) {
                this.lines.splice(index, 1);
                if (this.lines.length === 0) {
                    this.addLine();
                }
            },
            matches: function (line) {
                var query = String(line.query || '').trim().toLowerCase();
                var rows = this.catalog.filter(function (product) {
                    if (!query) return true;
                    return product.name.toLowerCase().indexOf(query) !== -1
                        || String(product.sku || '').toLowerCase().indexOf(query) !== -1;
                });
                return rows.slice(0, 8);
            },
            applyProduct: function (line, product, keepEnteredCost) {
                line.product_id = product.id;
                line.query = product.name;
                line.name = product.name;
                line.sku = product.sku;
                line.previous_cost = product.previous_cost == null ? null : product.previous_cost;
                line.previous_change = product.previous_change == null ? null : product.previous_change;
                line.unit_label = product.unit_label || 'unit';
                line.open = false;
                if (!keepEnteredCost) {
                    line.unit_cost = product.unit_cost;
                }
            },
            choose: function (line, product) {
                this.applyProduct(line, product, false);
            },
            changeLabel: function (line) {
                var pct = Number(line.previous_change);
                var label = (pct > 0 ? '+' : '') + pct.toFixed(1).replace(/\.0$/, '');
                return label + '%';
            },
            changeClass: function (line) {
                var pct = Number(line.previous_change);
                if (pct > 0) return 'text-emerald-600';
                if (pct < 0) return 'text-red-600';
                return 'text-gray-500';
            },
            subtotal: function (line) {
                var quantity = parseFloat(line.quantity);
                var cost = parseFloat(line.unit_cost);
                if (!isFinite(quantity) || !isFinite(cost)) return 0;
                return Math.round(quantity * cost * 100) / 100;
            },
            grandTotal: function () {
                var self = this;
                return this.lines.reduce(function (sum, line) {
                    if (!line.product_id) return sum;
                    return sum + self.subtotal(line);
                }, 0);
            },
            filledCount: function () {
                return this.lines.filter(function (line) { return line.product_id; }).length;
            },
            money: function (amount) {
                var formatted = Number(amount || 0).toLocaleString(undefined, { maximumFractionDigits: 0 });
                return String(this.currencySample || '').replace(/-?[\d,.]+/, formatted);
            },
            importCsv: function (event) {
                var file = event.target.files && event.target.files[0];
                event.target.value = '';
                if (!file) return;
                var self = this;
                var reader = new FileReader();
                reader.onload = function () {
                    self.applyCsv(String(reader.result || ''));
                };
                reader.readAsText(file);
            },
            applyCsv: function (text) {
                var parsed = this.parseCsv(text);
                var errors = [];
                var added = 0;
                var self = this;
                parsed.forEach(function (row, index) {
                    var sku = String(row.sku || '').trim().toLowerCase();
                    if (!sku) {
                        errors.push('Row ' + (index + 1) + ' is missing a SKU or barcode.');
                        return;
                    }
                    var product = self.catalog.find(function (item) {
                        return String(item.sku || '').trim().toLowerCase() === sku;
                    });
                    if (!product) {
                        errors.push('No product matches "' + row.sku + '".');
                        return;
                    }
                    var quantity = parseFloat(row.quantity);
                    var cost = parseFloat(row.unit_cost);
                    if (!isFinite(quantity) || quantity <= 0) {
                        errors.push('"' + row.sku + '" needs a quantity greater than zero.');
                        return;
                    }
                    var enteredCost = String(row.unit_cost || '').trim() !== '' && isFinite(cost) && cost >= 0;
                    self.lines.push({
                        product_id: '',
                        query: '',
                        name: '',
                        sku: '',
                        quantity: quantity,
                        unit_cost: enteredCost ? cost : product.unit_cost,
                        open: false
                    });
                    self.applyProduct(self.lines[self.lines.length - 1], product, enteredCost);
                    added += 1;
                });
                this.lines = this.lines.filter(function (line) { return line.product_id; });
                if (this.lines.length === 0) {
                    this.addLine();
                }
                this.csvErrors = errors;
                this.formError = added ? '' : (errors[0] || 'The CSV did not add any lines.');
            },
            parseCsv: function (text) {
                var rows = String(text || '').replace(/^\uFEFF/, '').split(/\r?\n/).filter(function (line) {
                    return line.trim() !== '';
                });
                if (!rows.length) return [];
                var header = this.splitCsv(rows[0]).map(function (cell) { return cell.trim().toLowerCase(); });
                var skuIndex = header.findIndex(function (cell) {
                    return cell === 'sku_or_barcode' || cell === 'sku' || cell === 'barcode';
                });
                var start = 0;
                var quantityIndex = 1;
                var costIndex = 2;
                if (skuIndex !== -1) {
                    start = 1;
                    quantityIndex = header.findIndex(function (cell) { return cell === 'quantity' || cell === 'qty'; });
                    costIndex = header.findIndex(function (cell) { return cell === 'unit_cost' || cell === 'cost'; });
                    if (quantityIndex === -1) quantityIndex = 1;
                    if (costIndex === -1) costIndex = 2;
                }
                var parsed = [];
                for (var i = start; i < rows.length; i++) {
                    var cells = this.splitCsv(rows[i]);
                    parsed.push({
                        sku: cells[skuIndex === -1 ? 0 : skuIndex] || '',
                        quantity: cells[quantityIndex] || '',
                        unit_cost: cells[costIndex] || ''
                    });
                }
                return parsed;
            },
            splitCsv: function (line) {
                var cells = [];
                var current = '';
                var quoted = false;
                for (var i = 0; i < line.length; i++) {
                    var char = line.charAt(i);
                    if (char === '"') {
                        if (quoted && line.charAt(i + 1) === '"') {
                            current += '"';
                            i += 1;
                        } else {
                            quoted = !quoted;
                        }
                    } else if (char === ',' && !quoted) {
                        cells.push(current.trim());
                        current = '';
                    } else {
                        current += char;
                    }
                }
                cells.push(current.trim());
                return cells;
            },
            onSubmit: function (event) {
                if (this.submitting) return;
                var valid = this.lines.filter(function (line) { return line.product_id; });
                if (!valid.length) {
                    event.preventDefault();
                    this.formError = 'Add at least one product.';
                    return;
                }
                var incomplete = valid.some(function (line) {
                    var quantity = parseFloat(line.quantity);
                    var cost = parseFloat(line.unit_cost);
                    return !isFinite(quantity) || quantity <= 0 || !isFinite(cost) || cost < 0;
                });
                if (incomplete) {
                    event.preventDefault();
                    this.formError = 'Each line needs a quantity and a unit cost.';
                    return;
                }
                event.preventDefault();
                this.lines = valid;
                this.submitting = true;
                this.formError = '';
                var form = event.target;
                this.$nextTick(function () { form.submit(); });
            }
        };
    });
});
</script>

@push('scripts')
<script>
(function () {
    function syncSupplierField(wrapper) {
        var select = wrapper.querySelector('.supplier-select');
        var newBox = wrapper.querySelector('.supplier-new');
        var nameInput = wrapper.querySelector('input[name="supplier_name"]');
        if (!select || !newBox) return;

        var isNew = select.value === '__new__' || (select.value === '' && select.options.length === 1);
        newBox.classList.toggle('hidden', !isNew);
        if (nameInput) {
            nameInput.required = isNew;
            if (!isNew) {
                nameInput.value = '';
            }
        }
    }

    document.querySelectorAll('[data-supplier-field]').forEach(function (wrapper) {
        var select = wrapper.querySelector('.supplier-select');
        if (!select) return;
        select.addEventListener('change', function () {
            syncSupplierField(wrapper);
        });
        syncSupplierField(wrapper);
    });

    document.querySelectorAll('[data-supplier-field]').forEach(function (wrapper) {
        var form = wrapper.closest('form');
        if (!form || form.dataset.supplierSubmitBound) {
            return;
        }
        form.dataset.supplierSubmitBound = '1';
        form.addEventListener('submit', function () {
            var select = wrapper.querySelector('.supplier-select');
            var nameInput = wrapper.querySelector('input[name="supplier_name"]');
            if (select && (select.value === '' || select.value === '__new__')) {
                select.removeAttribute('name');
            } else if (nameInput) {
                nameInput.removeAttribute('name');
            }
        });
    });
})();
</script>
@endpush
