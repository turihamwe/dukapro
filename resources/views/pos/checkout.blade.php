@extends('layouts.cashier')

@section('title', 'POS Checkout')

@section('content')
@php    $posCatalog = $products->map(function ($product) {
        return [
            'id' => $product->id,
            'name' => $product->displayName(),
            'price' => (float) $product->fifo_price,
            'stock_quantity' => (int) $product->available_stock,
        ];
    })->values();
@endphp

<div class="flex flex-col gap-4 lg:grid lg:grid-cols-5 lg:gap-6">
    <div class="order-2 lg:order-1 lg:col-span-3">
        <x-input type="search" id="productSearch" placeholder="Search product or SKU..." autofocus large class="mb-4" />

        <div id="productGrid" class="grid grid-cols-2 gap-2.5 sm:grid-cols-3 sm:gap-3">
            @forelse($products as $product)
                <div class="product-card" data-name="{{ strtolower($product->displayName() . ' ' . $product->name) }}" data-sku="{{ strtolower($product->sku ?? '') }}">
                    <button type="button" data-product-id="{{ $product->id }}"
                            class="pos-product group w-full rounded-xl border border-gray-100 bg-white p-3 text-left shadow-sm transition active:scale-[0.98] sm:p-4 hover:border-indigo-200 hover:shadow-md">
                        <p class="line-clamp-2 text-sm font-semibold text-gray-900 group-hover:text-indigo-600">{{ $product->displayName() }}</p>
                        @if($product->sku)
                            <p class="mt-0.5 truncate text-[10px] font-medium uppercase tracking-wide text-gray-400">{{ $product->sku }}</p>
                        @endif
                        <p class="mt-1 text-base font-bold text-indigo-600 sm:text-lg">@money($product->fifo_price)</p>
                        <p class="mt-1 text-[11px] text-gray-500">Stock: {{ format_unit_quantity($product->available_stock, $product->measurement_unit, auth()->user()->business_id) }}</p>
                    </button>
                </div>
            @empty
                <p class="col-span-full rounded-xl border border-dashed border-gray-200 bg-white p-8 text-center text-sm text-gray-500">No products in stock.</p>
            @endforelse
        </div>
    </div>

    <div class="order-1 lg:order-2 lg:col-span-2">
        <x-card :padding="false" class="shadow-lg">
            <div class="flex items-center justify-between border-b border-gray-100 bg-indigo-600 px-4 py-3 sm:px-5">
                <span class="font-semibold text-white">Cart</span>
                <span id="cartCount" class="rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-bold text-white">0</span>
            </div>

            <div id="cartItems" class="space-y-0 px-4 py-3 sm:px-5">
                <p class="text-sm text-gray-500">Tap products to add</p>
            </div>

            <div id="posCartFooter" class="space-y-3 border-t border-gray-100 p-4 sm:space-y-4 sm:p-5">
                <div id="posMetaSection" class="space-y-2 sm:space-y-3">                @if($waiterMode ?? false)
                    @php
                        $waiterOptions = ($floorStaff ?? collect())->mapWithKeys(function ($staff) {
                            return [$staff->id => $staff->name . ' (' . \App\Enums\UserRole::floorStaffLabel($staff->role) . ')'];
                        })->all();
                    @endphp
                    <x-choice-tabs-or-select
                        id="waiterId"
                        label="Waiter / Floor Staff"
                        :options="$waiterOptions"
                        required
                        placeholder="Select waiter…"
                        empty-message="No floor staff available in your branch"
                    />
                @endif

                @if($isHospitality ?? false)
                    @if($useRestaurantTables ?? false)
                        <x-choice-tabs-or-select
                            id="restaurantTableId"
                            label="Table"
                            :options="$restaurantTables"
                            required
                            placeholder="Select table…"
                            empty-message="No tables configured — add them in Business Profile"
                        />
                    @else
                        <x-input type="text" id="tableLabel" label="Table / area (optional)" placeholder="e.g. Table 4" maxlength="50" />
                    @endif
                @endif

                @if($restaurantMode ?? false)
                    <x-input type="text" id="orderNotes" label="Order notes (optional)" placeholder="General kitchen note…" />
                @endif
                </div>

                <div id="posPaymentSection" class="{{ ($restaurantMode ?? false) ? 'hidden space-y-2 sm:space-y-3' : 'space-y-2 sm:space-y-3' }}">
                <x-select id="paymentMethod" label="Payment">
                    <option value="">Select</option>
                    <option value="cash">Cash</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="bank">{{ ($waiterMode ?? false) ? 'Merchant Code / Bank' : 'Bank Transfer' }}</option>
                    <option value="credit">{{ ($waiterMode ?? false) ? 'Credit Tab (unpaid)' : 'Credit (Hardware)' }}</option>
                </x-select>

                @if($waiterMode ?? false)
                <div id="mobileProviderWrap" class="hidden">
                    <x-select id="mobileMoneyProvider" label="Mobile provider">
                        <option value="mtn">MTN MoMo</option>
                        <option value="airtel">Airtel Money</option>
                    </x-select>
                </div>
                @endif

                <div id="customerSelectWrap" class="hidden">
                    <x-select id="customerId" label="{{ ($waiterMode ?? false) ? 'Customer (optional for tabs)' : 'Credit Customer' }}">
                        <option value="">{{ ($waiterMode ?? false) ? 'Walk-in / no customer' : 'Select customer' }}</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} (Bal: @money($c->outstanding_balance))</option>
                        @endforeach
                    </x-select>
                </div>

                </div>

                <div id="posActionSection" class="space-y-2 border-t border-gray-100 pt-2 sm:pt-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Total</span>
                        <span id="cartTotal" class="text-lg font-bold text-gray-900 sm:text-xl">{{ auth()->user()->business->formatMoney(0) }}</span>
                    </div>

                @if($restaurantMode ?? false)
                    <x-button id="sendKitchenBtn" variant="success" size="lg" type="button" class="w-full min-h-[44px]" disabled>Send to Kitchen</x-button>
                    <button type="button" id="togglePayNowBtn" class="w-full text-center text-xs font-medium text-indigo-600 hover:text-indigo-800">Pay now (Counter sales)</button>
                @else
                <x-button id="checkoutBtn" variant="success" size="lg" type="button" class="w-full min-h-[44px]" disabled>Complete Sale</x-button>
                @endif
                @if($restaurantMode ?? false)
                    <x-button id="checkoutBtn" variant="secondary" size="lg" type="button" class="hidden w-full min-h-[44px]" disabled>Complete paid sale</x-button>
                @endif
                </div>
            </div>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script id="pos-catalog-data" type="application/json">@json($posCatalog)</script>
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var checkoutUrl = @json(tenant_route('tenant.pos.checkout'));
    var sendKitchenUrl = @json(tenant_route('tenant.pos.send-kitchen'));
    var waiterMode = @json($waiterMode ?? false);
    var restaurantMode = @json($restaurantMode ?? false);
    var useRestaurantTables = @json($useRestaurantTables ?? false);
    var currencySample = @json(auth()->user()->business->formatMoney(0));
    var POS_CATALOG = JSON.parse(document.getElementById('pos-catalog-data').textContent);
    var productById = {};
    POS_CATALOG.forEach(function (p) { productById[String(p.id)] = p; });

    var cart = [];
    var expandedIdx = null;

    function formatMoney(amount) {
        return currencySample.replace(/[\d,.]+/, Number(amount).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 }));
    }

    function parseQty(val) {
        var qty = parseInt(String(val), 10);
        return isNaN(qty) ? 0 : qty;
    }

    function addToCart(product, qty) {
        qty = parseQty(qty);
        if (!product || qty <= 0) return false;

        var maxStock = parseQty(product.stock_quantity);
        var existing = cart.find(function (i) {
            return restaurantMode
                ? lineKey(i) === String(product.id) + '|'
                : i.product_id === product.id;
        });
        var nextQty = existing ? existing.quantity + qty : qty;

        if (nextQty > maxStock) {
            alert('Insufficient stock for ' + product.name + '. Available: ' + maxStock);
            return false;
        }

        var targetIdx;
        if (existing) {
            existing.quantity = nextQty;
            targetIdx = cart.indexOf(existing);
        } else {
            cart.push({
                product_id: product.id,
                name: product.name,
                unit_price: parseFloat(product.price),
                quantity: qty,
                max_stock: maxStock,
                notes: '',
            });
            targetIdx = cart.length - 1;
        }

        if (restaurantMode) {
            expandedIdx = targetIdx;
        }

        renderCart();
        return true;
    }

    function lineKey(item) {
        return String(item.product_id) + '|' + (item.notes || '').trim();
    }

    function renderCart() {
        var wrap = document.getElementById('cartItems');
        var total = cart.reduce(function (s, i) { return s + i.quantity * i.unit_price; }, 0);
        var units = cart.reduce(function (s, i) { return s + i.quantity; }, 0);
        var hasItems = cart.length > 0;

        document.getElementById('cartTotal').textContent = formatMoney(total);
        document.getElementById('cartCount').textContent = String(units);
        if (!restaurantMode) {
            document.getElementById('checkoutBtn').disabled = !hasItems;
        }
        var sendBtn = document.getElementById('sendKitchenBtn');
        if (sendBtn) sendBtn.disabled = !hasItems;
        if (restaurantMode) {
            var checkoutBtn = document.getElementById('checkoutBtn');
            if (checkoutBtn) {
                checkoutBtn.disabled = !hasItems;
            }
        }

        if (!cart.length) {
            wrap.innerHTML = '<p class="text-sm text-gray-500">Tap products to add</p>';
            expandedIdx = null;
            return;
        }

        if (expandedIdx !== null && expandedIdx >= cart.length) {
            expandedIdx = null;
        }

        wrap.innerHTML = cart.map(function (item, idx) {
            var lineTotal = item.quantity * item.unit_price;
            var notePreview = (item.notes || '').trim();
            var isExpanded = !restaurantMode || expandedIdx === idx;

            if (isExpanded) {
                return '<div class="border-b border-gray-100 py-3 last:border-0" data-idx="' + idx + '">' +
                    '<div class="flex items-start justify-between gap-2">' +
                        '<div class="min-w-0 flex-1">' +
                            '<p class="truncate text-sm font-medium text-gray-900">' + esc(item.name) + '</p>' +
                            '<p class="text-xs text-gray-500">' + formatMoney(item.unit_price) + ' each · max ' + item.max_stock + '</p>' +
                        '</div>' +
                        (restaurantMode
                            ? '<button type="button" data-action="collapse" data-idx="' + idx + '" class="shrink-0 text-xs font-medium text-gray-500 hover:text-gray-700">Done</button>'
                            : '<button type="button" data-action="remove" data-idx="' + idx + '" class="min-h-[36px] min-w-[36px] shrink-0 rounded-lg border border-red-200 text-sm text-red-600 hover:bg-red-50" aria-label="Remove">×</button>') +
                    '</div>' +
                    (restaurantMode ? '<input type="text" data-action="notes" data-idx="' + idx + '" value="' + esc(item.notes || '') + '" placeholder="Item note (e.g. spiced)" maxlength="500" class="mt-2 w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">' : '') +
                    '<div class="mt-2 flex items-center gap-2">' +
                        '<button type="button" data-action="minus" data-idx="' + idx + '" class="min-h-[40px] min-w-[40px] rounded-lg border border-gray-300 text-sm hover:bg-gray-50">−</button>' +
                        '<input type="number" min="1" step="1" max="' + item.max_stock + '" value="' + item.quantity + '" data-action="qty" data-idx="' + idx + '" ' +
                            'class="w-20 min-h-[40px] rounded-lg border-gray-300 text-center text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">' +
                        '<button type="button" data-action="plus" data-idx="' + idx + '" class="min-h-[40px] min-w-[40px] rounded-lg border border-gray-300 text-sm hover:bg-gray-50">+</button>' +
                        '<span class="ml-auto text-sm font-semibold text-gray-900">' + formatMoney(lineTotal) + '</span>' +
                    '</div>' +
                    (restaurantMode ? '<button type="button" data-action="remove" data-idx="' + idx + '" class="mt-2 text-xs font-medium text-red-600 hover:text-red-700">Remove item</button>' : '') +
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
        setQty(idx, item.quantity + delta);
    }

    function setQty(idx, val) {
        var item = cart[idx];
        if (!item) return;

        var qty = parseQty(val);
        if (qty <= 0) {
            cart.splice(idx, 1);
            if (expandedIdx === idx) expandedIdx = null;
            else if (expandedIdx !== null && expandedIdx > idx) expandedIdx -= 1;
            renderCart();
            return;
        }
        if (qty > item.max_stock) {
            alert('Max stock is ' + item.max_stock);
            renderCart();
            return;
        }
        item.quantity = qty;
        renderCart();
    }

    function esc(text) {
        var el = document.createElement('span');
        el.textContent = text;
        return el.innerHTML;
    }

    document.getElementById('productGrid').addEventListener('click', function (e) {
        var btn = e.target.closest('[data-product-id]');
        if (!btn) return;
        var product = productById[btn.getAttribute('data-product-id')];
        if (product) addToCart(product, 1);
    });

    document.getElementById('cartItems').addEventListener('input', function (e) {
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

    document.getElementById('cartItems').addEventListener('click', function (e) {
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

    document.getElementById('cartItems').addEventListener('change', function (e) {
        var input = e.target.closest('[data-action="qty"]');
        if (!input) return;
        setQty(parseInt(input.getAttribute('data-idx'), 10), input.value);
    });

    document.getElementById('productSearch').addEventListener('input', function () {
        var q = this.value.toLowerCase();
        document.querySelectorAll('.product-card').forEach(function (el) {
            var match = el.dataset.name.includes(q) || el.dataset.sku.includes(q);
            el.classList.toggle('hidden', q !== '' && !match);
        });
    });

    document.getElementById('paymentMethod').addEventListener('change', function () {
        var method = this.value;
        document.getElementById('customerSelectWrap').classList.toggle('hidden', method !== 'credit');
        if (waiterMode) {
            var mobileWrap = document.getElementById('mobileProviderWrap');
            if (mobileWrap) {
                mobileWrap.classList.toggle('hidden', method !== 'mobile_money');
            }
        }
    });

    if (restaurantMode) {
        document.getElementById('togglePayNowBtn').addEventListener('click', function () {
            var paymentSection = document.getElementById('posPaymentSection');
            var checkoutBtn = document.getElementById('checkoutBtn');
            var sendBtn = document.getElementById('sendKitchenBtn');
            var showingPay = !paymentSection.classList.contains('hidden');
            paymentSection.classList.toggle('hidden', showingPay);
            checkoutBtn.classList.toggle('hidden', showingPay);
            sendBtn.classList.toggle('hidden', !showingPay);
            checkoutBtn.disabled = cart.length === 0;
            this.textContent = showingPay ? 'Pay now (Counter sales)' : 'Back to send-to-kitchen';
        });

        document.getElementById('sendKitchenBtn').addEventListener('click', async function () {
            var waiterId = waiterMode ? document.getElementById('waiterId').value : null;
            if (waiterMode && !waiterId) {
                alert('Select the waiter or floor staff for this order');
                return;
            }
            var tablePayload = {};
            if (useRestaurantTables) {
                var tableId = document.getElementById('restaurantTableId').value;
                if (!tableId) {
                    alert('Select a table for this order');
                    return;
                }
                tablePayload.restaurant_table_id = parseInt(tableId, 10);
            } else {
                var tableLabelEl = document.getElementById('tableLabel');
                tablePayload.table_label = tableLabelEl ? tableLabelEl.value.trim() || null : null;
            }

            this.disabled = true;
            try {
                var res = await fetch(sendKitchenUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(Object.assign({
                        items: cart.map(function (i) {
                            return { product_id: i.product_id, quantity: i.quantity, notes: i.notes || null };
                        }),
                        notes: document.getElementById('orderNotes').value.trim() || null,
                        waiter_id: waiterId || null,
                    }, tablePayload)),
                });
                var data = await res.json();
                if (!res.ok) {
                    throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Could not send order'));
                }
                cart = [];
                expandedIdx = null;
                renderCart();
                if (document.getElementById('orderNotes')) document.getElementById('orderNotes').value = '';
                if (document.getElementById('tableLabel')) document.getElementById('tableLabel').value = '';
                if (window.resetChoicePicker) {
                    resetChoicePicker('restaurantTableId');
                } else if (document.getElementById('restaurantTableId')) {
                    document.getElementById('restaurantTableId').value = '';
                }
                alert(data.message || ('Order ' + data.order.order_number + ' sent to kitchen.'));
            } catch (err) {
                alert(err.message);
            } finally {
                document.getElementById('sendKitchenBtn').disabled = cart.length === 0;
            }
        });
    }

    document.getElementById('checkoutBtn').addEventListener('click', async function () {
        var paymentMethod = document.getElementById('paymentMethod').value;
        var customerId = document.getElementById('customerId').value;
        var waiterId = waiterMode ? document.getElementById('waiterId').value : null;
        var mobileProviderEl = document.getElementById('mobileMoneyProvider');
        var mobileProvider = mobileProviderEl ? mobileProviderEl.value : null;

        if (waiterMode && !waiterId) {
            alert('Select the waiter or floor staff for this order');
            return;
        }
        if (paymentMethod === 'credit' && !waiterMode && !customerId) {
            alert('Select a customer for credit sale');
            return;
        }
        if (waiterMode && paymentMethod === 'mobile_money' && !mobileProvider) {
            alert('Select Airtel or MTN for mobile money');
            return;
        }

        var tablePayload = {};
        if (restaurantMode) {
            if (useRestaurantTables) {
                var tableId = document.getElementById('restaurantTableId').value;
                if (!tableId) {
                    alert('Select a table for this order');
                    return;
                }
                tablePayload.restaurant_table_id = parseInt(tableId, 10);
            } else {
                var tableLabelEl = document.getElementById('tableLabel');
                tablePayload.table_label = tableLabelEl ? tableLabelEl.value.trim() || null : null;
            }
        }

        this.disabled = true;
        try {
            var checkoutPayload = {
                items: cart.map(function (i) {
                    return {
                        product_id: i.product_id,
                        quantity: i.quantity,
                        unit_price: i.unit_price,
                        notes: i.notes || null,
                    };
                }),
                payment_method: paymentMethod,
                mobile_money_provider: paymentMethod === 'mobile_money' ? mobileProvider : null,
                customer_id: customerId || null,
                waiter_id: waiterId || null,
                is_credit_sale: paymentMethod === 'credit',
            };
            if (restaurantMode) {
                checkoutPayload.notes = document.getElementById('orderNotes').value.trim() || null;
                Object.assign(checkoutPayload, tablePayload);
            }
            var res = await fetch(checkoutUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(checkoutPayload),
            });
            var data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Checkout failed'));
            }
            cart = [];
            expandedIdx = null;
            renderCart();
            alert('Sale ' + data.sale.sale_number + ' completed!');
            location.reload();
        } catch (err) {
            alert(err.message);
        } finally {
            document.getElementById('checkoutBtn').disabled = cart.length === 0;
        }
    });
})();
</script>
@endpush
