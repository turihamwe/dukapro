@extends('layouts.restaurant-staff')

@section('title', 'Take Order')
@section('main_class', 'lg:!py-2')

@section('content')
@php
    $menuCatalog = $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'sku' => $product->sku,
        ];
    })->values();
@endphp

<x-page-header title="Take Order" subtitle="Tap items to add — review the order, then send to kitchen." class="!mb-4" />

<div class="flex flex-col gap-4 lg:grid lg:grid-cols-5 lg:gap-6">
    <div class="order-2 lg:order-1 lg:col-span-3">
        <x-input type="search" id="menu-search" placeholder="Search menu items…" autofocus large class="mb-4" />

        <div id="menu-grid" class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3">
            @forelse($products as $product)
                <div class="menu-card" data-name="{{ strtolower($product->name . ' ' . ($product->sku ?? '')) }}">
                    <button type="button" data-product-id="{{ $product->id }}"
                            class="menu-item group w-full rounded-xl border border-gray-100 bg-white p-3 text-left shadow-sm transition active:scale-[0.98] sm:p-4 hover:border-emerald-200 hover:shadow-md">
                        <p class="line-clamp-2 text-sm font-semibold text-gray-900 group-hover:text-emerald-700">{{ $product->name }}</p>
                        @if($product->sku)
                            <p class="mt-0.5 truncate text-[10px] font-medium uppercase tracking-wide text-gray-400">{{ $product->sku }}</p>
                        @endif
                        <p class="mt-1 text-base font-bold text-emerald-600 sm:text-lg">@money($product->price)</p>
                    </button>
                </div>
            @empty
                <p class="col-span-full rounded-xl border border-dashed border-gray-200 bg-white p-8 text-center text-sm text-gray-500">No menu items yet.</p>
            @endforelse
        </div>
    </div>

    <div class="order-1 lg:order-2 lg:col-span-2">
        <x-card :padding="false" class="sticky top-[4.5rem] overflow-hidden shadow-lg lg:top-20">
            <div class="flex items-center justify-between border-b border-gray-100 bg-emerald-600 px-4 py-3 sm:px-5">
                <span class="font-semibold text-white">Order</span>
                <span id="cart-count" class="rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-bold text-white">0</span>
            </div>

            <div id="cart-items" class="space-y-0 px-4 py-3 sm:px-5">
                <p class="text-sm text-gray-500">Tap menu items to add</p>
            </div>

            <div class="space-y-3 border-t border-gray-100 p-4 sm:space-y-4 sm:p-5">
                @if($useRestaurantTables ?? false)
                    <x-choice-tabs-or-select
                        id="restaurant-table-id"
                        label="Table"
                        :options="$restaurantTables"
                        required
                        placeholder="Select table…"
                        empty-message="No tables configured — ask your manager to add tables"
                        variant="emerald"
                    />
                @else
                    <x-input type="text" id="table-label" label="Table / area (optional)" placeholder="e.g. Table 4" maxlength="50" />
                @endif
                <x-input type="text" id="order-notes" label="Order notes (optional)" placeholder="General note for kitchen…" maxlength="500" />

                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Subtotal</span>
                    <span id="cart-subtotal" class="text-xl font-bold text-gray-900">{{ $business->formatMoney(0) }}</span>
                </div>

                <x-button id="place-order-btn" variant="success" size="lg" type="button" class="w-full min-h-[48px]" disabled>
                    Send to Kitchen
                </x-button>
            </div>
        </x-card>
    </div>
</div>

<div id="order-toast" class="pointer-events-none fixed bottom-24 left-1/2 z-[100] hidden -translate-x-1/2 rounded-xl bg-gray-900 px-4 py-3 text-sm font-medium text-white shadow-lg"></div>
@endsection

@push('scripts')
<script id="menu-catalog-data" type="application/json">@json($menuCatalog)</script>
<script>
(function () {
    var MENU_CATALOG = JSON.parse(document.getElementById('menu-catalog-data').textContent);
    var productById = {};
    MENU_CATALOG.forEach(function (p) { productById[String(p.id)] = p; });

    var placeUrl = @json(tenant_route('tenant.waiter-orders.place'));
    var csrf = @json(csrf_token());
    var useRestaurantTables = @json($useRestaurantTables ?? false);
    var currencySample = @json($business->formatMoney(0));

    var cart = [];
    var expandedIdx = null;
    var placing = false;

    function formatMoney(amount) {
        return currencySample.replace(/[\d,.]+/, Number(amount).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 }));
    }

    function esc(text) {
        var el = document.createElement('span');
        el.textContent = text == null ? '' : String(text);
        return el.innerHTML;
    }

    function lineKey(item) {
        return String(item.product_id) + '|' + (item.notes || '').trim();
    }

    function addToCart(product, qty) {
        qty = parseInt(String(qty), 10);
        if (!product || isNaN(qty) || qty <= 0) return;

        var existing = cart.find(function (i) { return lineKey(i) === String(product.id) + '|'; });
        var targetIdx;
        if (existing) {
            existing.quantity += qty;
            targetIdx = cart.indexOf(existing);
        } else {
            cart.push({
                product_id: product.id,
                name: product.name,
                unit_price: parseFloat(product.price),
                quantity: qty,
                notes: '',
            });
            targetIdx = cart.length - 1;
        }
        expandedIdx = targetIdx;
        renderCart();
    }

    function renderCart() {
        var wrap = document.getElementById('cart-items');
        var placeBtn = document.getElementById('place-order-btn');
        var hasItems = cart.length > 0;
        var units = cart.reduce(function (s, i) { return s + i.quantity; }, 0);
        var subtotal = cart.reduce(function (s, i) { return s + i.quantity * i.unit_price; }, 0);

        document.getElementById('cart-count').textContent = String(units);
        document.getElementById('cart-subtotal').textContent = formatMoney(subtotal);
        placeBtn.disabled = !hasItems || placing;

        if (!hasItems) {
            wrap.innerHTML = '<p class="text-sm text-gray-500">Tap menu items to add</p>';
            expandedIdx = null;
            return;
        }
        if (expandedIdx !== null && expandedIdx >= cart.length) {
            expandedIdx = null;
        }

        wrap.innerHTML = cart.map(function (item, idx) {
            var lineTotal = item.quantity * item.unit_price;
            var notePreview = (item.notes || '').trim();
            var isExpanded = expandedIdx === idx;

            if (isExpanded) {
                return '<div class="border-b border-gray-100 py-3 last:border-0" data-idx="' + idx + '">' +
                    '<div class="flex items-start justify-between gap-2">' +
                        '<div class="min-w-0 flex-1">' +
                            '<p class="truncate text-sm font-medium text-gray-900">' + esc(item.name) + '</p>' +
                            '<p class="text-xs text-gray-500">' + formatMoney(item.unit_price) + ' each</p>' +
                        '</div>' +
                        '<button type="button" data-action="collapse" data-idx="' + idx + '" class="shrink-0 text-xs font-medium text-gray-500 hover:text-gray-700">Done</button>' +
                    '</div>' +
                    '<div class="mt-2 flex items-center gap-2">' +
                        '<button type="button" data-action="minus" data-idx="' + idx + '" class="min-h-[40px] min-w-[40px] rounded-lg border border-gray-300 text-sm hover:bg-gray-50">−</button>' +
                        '<input type="number" min="1" step="1" value="' + item.quantity + '" data-action="qty" data-idx="' + idx + '" class="w-20 min-h-[40px] rounded-lg border-gray-300 text-center text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">' +
                        '<button type="button" data-action="plus" data-idx="' + idx + '" class="min-h-[40px] min-w-[40px] rounded-lg border border-gray-300 text-sm hover:bg-gray-50">+</button>' +
                        '<span class="ml-auto text-sm font-semibold text-gray-900">' + formatMoney(lineTotal) + '</span>' +
                    '</div>' +
                    '<input type="text" data-action="notes" data-idx="' + idx + '" value="' + esc(item.notes || '') + '" placeholder="Item note (e.g. spiced)" maxlength="500" class="mt-2 w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">' +
                    '<button type="button" data-action="remove" data-idx="' + idx + '" class="mt-2 text-xs font-medium text-red-600 hover:text-red-700">Remove item</button>' +
                '</div>';
            }

            return '<button type="button" data-action="expand" data-idx="' + idx + '" class="flex w-full items-start justify-between gap-2 border-b border-gray-100 py-3 text-left last:border-0 hover:bg-gray-50/80">' +
                '<div class="min-w-0 flex-1">' +
                    '<p class="truncate text-sm font-medium text-gray-900">' + item.quantity + '× ' + esc(item.name) + '</p>' +
                    (notePreview ? '<p class="mt-0.5 truncate text-xs text-orange-700">' + esc(notePreview) + '</p>' : '') +
                    '<p class="mt-0.5 text-xs text-gray-500">' + formatMoney(item.unit_price) + ' each</p>' +
                '</div>' +
                '<span class="shrink-0 text-sm font-semibold text-gray-900">' + formatMoney(lineTotal) + '</span>' +
            '</button>';
        }).join('');
    }

    function changeQty(idx, delta) {
        var item = cart[idx];
        if (!item) return;
        var next = item.quantity + delta;
        if (next <= 0) {
            cart.splice(idx, 1);
            if (expandedIdx === idx) expandedIdx = null;
            else if (expandedIdx !== null && expandedIdx > idx) expandedIdx -= 1;
        } else {
            item.quantity = next;
        }
        renderCart();
    }

    function setQty(idx, val) {
        var item = cart[idx];
        if (!item) return;
        var qty = parseInt(String(val), 10);
        if (isNaN(qty) || qty <= 0) {
            cart.splice(idx, 1);
            if (expandedIdx === idx) expandedIdx = null;
            else if (expandedIdx !== null && expandedIdx > idx) expandedIdx -= 1;
        } else {
            item.quantity = qty;
        }
        renderCart();
    }

    function resetOrder() {
        cart = [];
        expandedIdx = null;
        if (useRestaurantTables) {
            if (window.resetChoicePicker) {
                resetChoicePicker('restaurant-table-id');
            } else {
                var tableSelect = document.getElementById('restaurant-table-id');
                if (tableSelect) tableSelect.value = '';
            }
        } else {
            var tableLabel = document.getElementById('table-label');
            if (tableLabel) tableLabel.value = '';
        }
        document.getElementById('order-notes').value = '';
        renderCart();
    }

    function showToast(message) {
        var toast = document.getElementById('order-toast');
        toast.textContent = message;
        toast.classList.remove('hidden');
        clearTimeout(showToast._timer);
        showToast._timer = setTimeout(function () { toast.classList.add('hidden'); }, 2500);
    }

    document.getElementById('menu-grid').addEventListener('click', function (e) {
        var btn = e.target.closest('[data-product-id]');
        if (!btn) return;
        var product = productById[btn.getAttribute('data-product-id')];
        if (product) addToCart(product, 1);
    });

    document.getElementById('cart-items').addEventListener('click', function (e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;
        var idx = parseInt(btn.getAttribute('data-idx'), 10);
        var action = btn.getAttribute('data-action');
        if (action === 'expand') {
            expandedIdx = idx;
            renderCart();
        } else if (action === 'collapse') {
            expandedIdx = null;
            renderCart();
        } else if (action === 'remove') {
            cart.splice(idx, 1);
            if (expandedIdx === idx) expandedIdx = null;
            else if (expandedIdx !== null && expandedIdx > idx) expandedIdx -= 1;
            renderCart();
        } else if (action === 'plus') {
            changeQty(idx, 1);
        } else if (action === 'minus') {
            changeQty(idx, -1);
        }
    });

    document.getElementById('cart-items').addEventListener('input', function (e) {
        var input = e.target.closest('[data-action="notes"]');
        if (!input) return;
        var idx = parseInt(input.getAttribute('data-idx'), 10);
        if (!cart[idx]) return;
        var oldKey = lineKey(cart[idx]);
        cart[idx].notes = input.value;
        var newKey = lineKey(cart[idx]);
        if (oldKey !== newKey) {
            var duplicate = cart.findIndex(function (item, i) { return i !== idx && lineKey(item) === newKey; });
            if (duplicate >= 0) {
                cart[duplicate].quantity += cart[idx].quantity;
                cart.splice(idx, 1);
                expandedIdx = duplicate;
                renderCart();
            }
        }
    });

    document.getElementById('cart-items').addEventListener('change', function (e) {
        var input = e.target.closest('[data-action="qty"]');
        if (!input) return;
        setQty(parseInt(input.getAttribute('data-idx'), 10), input.value);
    });

    document.getElementById('place-order-btn').addEventListener('click', function () {
        if (placing || cart.length === 0) return;
        placing = true;
        var placeBtn = document.getElementById('place-order-btn');
        placeBtn.disabled = true;
        placeBtn.textContent = 'Sending…';

        var payload = {
            notes: document.getElementById('order-notes').value.trim() || null,
            items: cart.map(function (line) {
                return {
                    product_id: line.product_id,
                    quantity: line.quantity,
                    notes: (line.notes || '').trim() || null,
                };
            }),
        };

        if (useRestaurantTables) {
            var tableId = document.getElementById('restaurant-table-id').value;
            if (!tableId) {
                showToast('Select a table.');
                placing = false;
                placeBtn.disabled = false;
                placeBtn.textContent = 'Send to Kitchen';
                return;
            }
            payload.restaurant_table_id = parseInt(tableId, 10);
        } else {
            var tableLabelEl = document.getElementById('table-label');
            payload.table_label = tableLabelEl ? tableLabelEl.value.trim() || null : null;
        }

        fetch(placeUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        }).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) throw data;
                return data;
            });
        }).then(function (data) {
            resetOrder();
            showToast(data.message || 'Order sent to kitchen.');
        }).catch(function (err) {
            var msg = (err && err.message) || (err && err.errors && Object.values(err.errors)[0][0]) || 'Could not send order.';
            showToast(msg);
        }).finally(function () {
            placing = false;
            placeBtn.textContent = 'Send to Kitchen';
            renderCart();
        });
    });

    document.getElementById('menu-search').addEventListener('input', function () {
        var q = this.value.toLowerCase().trim();
        document.querySelectorAll('.menu-card').forEach(function (el) {
            var match = el.dataset.name.includes(q);
            el.classList.toggle('hidden', q !== '' && !match);
        });
    });
})();
</script>
@endpush
