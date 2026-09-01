@extends('layouts.cashier')

@section('title', 'POS Checkout')

@section('content')
@php
    $posCatalog = $products->map(function ($product) {
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
        <x-card :padding="false" class="sticky top-[4.5rem] overflow-hidden shadow-lg lg:top-20">
            <div class="flex items-center justify-between border-b border-gray-100 bg-indigo-600 px-4 py-3 sm:px-5">
                <span class="font-semibold text-white">Cart</span>
                <span id="cartCount" class="rounded-full bg-white/20 px-2.5 py-0.5 text-xs font-bold text-white">0</span>
            </div>

            <div id="cartItems" class="max-h-[34vh] space-y-0 overflow-y-auto px-4 py-3 sm:max-h-[40vh] sm:px-5">
                <p class="text-sm text-gray-500">Tap products to add</p>
            </div>

            <div class="space-y-3 border-t border-gray-100 p-4 sm:space-y-4 sm:p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Total</span>
                    <span id="cartTotal" class="text-xl font-bold text-gray-900">0.00</span>
                </div>

                <x-select id="paymentMethod" label="Payment">
                    <option value="cash">Cash</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="bank">Bank Transfer</option>
                    <option value="credit">Credit (Hardware)</option>
                </x-select>

                <div id="customerSelectWrap" class="hidden">
                    <x-select id="customerId" label="Credit Customer">
                        <option value="">Select customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->name }} (Bal: @money($c->outstanding_balance))</option>
                        @endforeach
                    </x-select>
                </div>

                <x-button id="checkoutBtn" variant="success" size="lg" type="button" class="w-full min-h-[48px]" disabled>Complete Sale</x-button>
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
    var POS_CATALOG = JSON.parse(document.getElementById('pos-catalog-data').textContent);
    var productById = {};
    POS_CATALOG.forEach(function (p) { productById[String(p.id)] = p; });

    var cart = [];

    function parseQty(val) {
        var qty = parseInt(String(val), 10);
        return isNaN(qty) ? 0 : qty;
    }

    function addToCart(product, qty) {
        qty = parseQty(qty);
        if (!product || qty <= 0) return false;

        var maxStock = parseQty(product.stock_quantity);
        var existing = cart.find(function (i) { return i.product_id === product.id; });
        var nextQty = existing ? existing.quantity + qty : qty;

        if (nextQty > maxStock) {
            alert('Insufficient stock for ' + product.name + '. Available: ' + maxStock);
            return false;
        }

        if (existing) {
            existing.quantity = nextQty;
        } else {
            cart.push({
                product_id: product.id,
                name: product.name,
                unit_price: parseFloat(product.price),
                quantity: qty,
                max_stock: maxStock,
            });
        }

        renderCart();
        return true;
    }

    function renderCart() {
        var wrap = document.getElementById('cartItems');
        var total = cart.reduce(function (s, i) { return s + i.quantity * i.unit_price; }, 0);
        var units = cart.reduce(function (s, i) { return s + i.quantity; }, 0);

        document.getElementById('cartTotal').textContent = total.toFixed(2);
        document.getElementById('cartCount').textContent = String(units);
        document.getElementById('checkoutBtn').disabled = cart.length === 0;

        if (!cart.length) {
            wrap.innerHTML = '<p class="text-sm text-gray-500">Tap products to add</p>';
            return;
        }

        wrap.innerHTML = cart.map(function (item, idx) {
            return '<div class="border-b border-gray-100 py-3 last:border-0">' +
                '<div class="flex items-start justify-between gap-2">' +
                    '<div class="min-w-0 flex-1">' +
                        '<p class="truncate text-sm font-medium text-gray-900">' + esc(item.name) + '</p>' +
                        '<p class="text-xs text-gray-500">' + item.unit_price.toFixed(2) + ' each · max ' + item.max_stock + '</p>' +
                    '</div>' +
                    '<button type="button" data-action="remove" data-idx="' + idx + '" class="min-h-[36px] min-w-[36px] shrink-0 rounded-lg border border-red-200 text-sm text-red-600 hover:bg-red-50" aria-label="Remove">×</button>' +
                '</div>' +
                '<div class="mt-2 flex items-center gap-2">' +
                    '<button type="button" data-action="minus" data-idx="' + idx + '" class="min-h-[40px] min-w-[40px] rounded-lg border border-gray-300 text-sm hover:bg-gray-50">−</button>' +
                    '<input type="number" min="1" step="1" max="' + item.max_stock + '" value="' + item.quantity + '" data-action="qty" data-idx="' + idx + '" ' +
                        'class="w-20 min-h-[40px] rounded-lg border-gray-300 text-center text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">' +
                    '<button type="button" data-action="plus" data-idx="' + idx + '" class="min-h-[40px] min-w-[40px] rounded-lg border border-gray-300 text-sm hover:bg-gray-50">+</button>' +
                    '<span class="ml-auto text-sm font-semibold text-gray-900">' + (item.quantity * item.unit_price).toFixed(2) + '</span>' +
                '</div>' +
            '</div>';
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

    document.getElementById('cartItems').addEventListener('click', function (e) {
        var btn = e.target.closest('[data-action]');
        if (!btn) return;
        var idx = parseInt(btn.getAttribute('data-idx'), 10);
        var action = btn.getAttribute('data-action');
        if (action === 'remove') {
            cart.splice(idx, 1);
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
        document.getElementById('customerSelectWrap').classList.toggle('hidden', this.value !== 'credit');
    });

    document.getElementById('checkoutBtn').addEventListener('click', async function () {
        var paymentMethod = document.getElementById('paymentMethod').value;
        var customerId = document.getElementById('customerId').value;
        if (paymentMethod === 'credit' && !customerId) {
            alert('Select a customer for credit sale');
            return;
        }

        this.disabled = true;
        try {
            var res = await fetch(checkoutUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    items: cart.map(function (i) {
                        return { product_id: i.product_id, quantity: i.quantity, unit_price: i.unit_price };
                    }),
                    payment_method: paymentMethod,
                    customer_id: customerId || null,
                    is_credit_sale: paymentMethod === 'credit',
                }),
            });
            var data = await res.json();
            if (!res.ok) {
                throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Checkout failed'));
            }
            cart = [];
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
