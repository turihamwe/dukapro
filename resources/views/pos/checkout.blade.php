@extends('layouts.cashier')

@section('title', 'POS Checkout')

@section('content')
<div class="grid gap-6 lg:grid-cols-5">
    {{-- Product grid --}}
    <div class="lg:col-span-3">
        <x-input type="search" id="productSearch" placeholder="Search product or SKU..." autofocus large class="mb-4" />

        <div id="productGrid" class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            @foreach($products as $product)
                <div class="product-card" data-name="{{ strtolower($product->name) }}" data-sku="{{ strtolower($product->sku ?? '') }}">
                    <button type="button" onclick="addToCart({{ json_encode($product) }})"
                            class="pos-product group w-full rounded-xl border border-gray-100 bg-white p-4 text-left shadow-sm transition hover:border-indigo-200 hover:shadow-md active:scale-[0.98]">
                        <p class="text-sm font-medium text-gray-900 group-hover:text-indigo-600">{{ $product->name }}</p>
                        <p class="mt-1 text-lg font-semibold text-indigo-600">@money($product->price)</p>
                        <p class="mt-1 text-xs text-gray-500">Stock: {{ $product->stock_quantity }} {{ $product->measurement_unit }}</p>
                    </button>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Cart sidebar --}}
    <div class="lg:col-span-2">
        <x-card :padding="false" class="sticky top-20 overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 bg-indigo-600 px-5 py-3">
                <span class="font-semibold text-white">Cart</span>
                <span id="cartCount" class="rounded-full bg-white/20 px-2 py-0.5 text-xs font-medium text-white">0</span>
            </div>

            <div id="cartItems" class="max-h-[40vh] space-y-0 overflow-y-auto px-5 py-3">
                <p class="text-sm text-gray-500">Tap products to add</p>
            </div>

            <div class="space-y-4 border-t border-gray-100 p-5">
                <div class="flex items-center justify-between">
                    <span class="text-sm text-gray-500">Total</span>
                    <span id="cartTotal" class="text-xl font-semibold text-gray-900">0.00</span>
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

                <x-button id="checkoutBtn" variant="success" size="lg" type="button" disabled>Complete Sale</x-button>
            </div>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrf = document.querySelector('meta[name="csrf-token"]').content;
let cart = [];

document.getElementById('productSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.product-card').forEach(el => {
        const match = el.dataset.name.includes(q) || el.dataset.sku.includes(q);
        el.classList.toggle('hidden', !match);
    });
});

document.getElementById('paymentMethod').addEventListener('change', function() {
    document.getElementById('customerSelectWrap').classList.toggle('hidden', this.value !== 'credit');
});

function addToCart(product) {
    const existing = cart.find(i => i.product_id === product.id);
    if (existing) {
        if (existing.quantity >= parseFloat(product.stock_quantity)) return alert('Max stock reached');
        existing.quantity += 1;
    } else {
        cart.push({ product_id: product.id, name: product.name, unit_price: parseFloat(product.price), quantity: 1, max_stock: parseFloat(product.stock_quantity) });
    }
    renderCart();
}

function renderCart() {
    const wrap = document.getElementById('cartItems');
    const total = cart.reduce((s, i) => s + i.quantity * i.unit_price, 0);
    document.getElementById('cartTotal').textContent = total.toFixed(2);
    document.getElementById('cartCount').textContent = cart.length;
    document.getElementById('checkoutBtn').disabled = cart.length === 0;

    if (!cart.length) {
        wrap.innerHTML = '<p class="text-sm text-gray-500">Tap products to add</p>';
        return;
    }

    wrap.innerHTML = cart.map((item, idx) => `
        <div class="border-b border-gray-100 py-3 last:border-0">
            <div class="flex items-center justify-between gap-2">
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-gray-900">${item.name}</p>
                    <p class="text-xs text-gray-500">${item.unit_price.toFixed(2)} × ${item.quantity}</p>
                </div>
                <div class="flex shrink-0 gap-1">
                    <button type="button" onclick="changeQty(${idx}, -1)" class="rounded-lg border border-gray-300 px-2 py-1 text-sm shadow-sm hover:bg-gray-50">−</button>
                    <button type="button" onclick="changeQty(${idx}, 1)" class="rounded-lg border border-gray-300 px-2 py-1 text-sm shadow-sm hover:bg-gray-50">+</button>
                    <button type="button" onclick="removeItem(${idx})" class="rounded-lg border border-red-200 px-2 py-1 text-sm text-red-600 shadow-sm hover:bg-red-50">×</button>
                </div>
            </div>
        </div>
    `).join('');
}

function changeQty(idx, delta) {
    const item = cart[idx];
    const next = item.quantity + delta;
    if (next <= 0) { cart.splice(idx, 1); }
    else if (next <= item.max_stock) { item.quantity = next; }
    else { return alert('Max stock reached'); }
    renderCart();
}

function removeItem(idx) { cart.splice(idx, 1); renderCart(); }

document.getElementById('checkoutBtn').addEventListener('click', async function() {
    const paymentMethod = document.getElementById('paymentMethod').value;
    const customerId = document.getElementById('customerId').value;
    if (paymentMethod === 'credit' && !customerId) return alert('Select a customer for credit sale');

    this.disabled = true;
    try {
        const res = await fetch('{{ tenant_route('tenant.pos.checkout') }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            body: JSON.stringify({
                items: cart.map(i => ({ product_id: i.product_id, quantity: i.quantity, unit_price: i.unit_price })),
                payment_method: paymentMethod,
                customer_id: customerId || null,
                is_credit_sale: paymentMethod === 'credit'
            })
        });
        const data = await res.json();
        if (!res.ok) throw new Error(data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Checkout failed'));
        cart = [];
        renderCart();
        alert('Sale ' + data.sale.sale_number + ' completed!');
        location.reload();
    } catch (e) {
        alert(e.message);
    } finally {
        this.disabled = cart.length === 0;
    }
});
</script>
@endpush
