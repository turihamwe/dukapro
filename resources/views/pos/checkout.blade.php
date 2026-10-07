@extends('layouts.cashier')

@section('title', 'POS Checkout')

@section('content')
@php
    $posCatalog = $products->map(function ($product) use ($divisibleProductsMode) {
        $isService = $product->isService();
        $baseStock = (float) $product->available_stock;
        $fifoBasePrice = (float) $product->fifo_price;
        $units = collect($product->pos_units ?? [])->map(function ($unit) use ($baseStock, $fifoBasePrice, $divisibleProductsMode, $isService) {
            $factor = (float) ($unit['factor'] ?? 1);
            $unitPrice = isset($unit['price']) && $unit['price'] > 0
                ? (float) $unit['price']
                : round($fifoBasePrice * $factor, 2);

            if ($isService) {
                $maxStock = 999999;
            } else {
                $maxStock = $factor > 0 ? round($baseStock / $factor, 3) : 0;
                if (! $divisibleProductsMode) {
                    $maxStock = floor($maxStock);
                }
            }

            return [
                'id' => $unit['id'],
                'name' => $unit['name'],
                'factor' => $factor,
                'price' => $unitPrice,
                'is_base' => (bool) ($unit['is_base'] ?? false),
                'max_stock' => $maxStock,
            ];
        })->values();

        if ($units->isEmpty()) {
            $units = collect([[
                'id' => null,
                'name' => $product->measurement_unit,
                'factor' => 1,
                'price' => $fifoBasePrice,
                'is_base' => true,
                'max_stock' => $isService ? 999999 : $baseStock,
            ]]);
        }

        $defaultUnit = $units->firstWhere('is_base', true) ?? $units->first();

        return [
            'id' => $product->id,
            'name' => $product->displayName(),
            'price' => (float) $defaultUnit['price'],
            'base_price' => (float) $product->price,
            'base_stock' => $baseStock,
            'is_service' => $isService,
            'measurement_unit' => $product->measurement_unit,
            'units' => $units->all(),
        ];
    })->values();
@endphp

@include('layouts.partials.low-stock-alert', ['lowStockItems' => $lowStockItems ?? collect()])

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
                        @if($product->isService())
                            <p class="mt-1 text-[11px] font-medium text-violet-600">Service · no stock</p>
                        @else
                            <p class="mt-1 text-[11px] text-gray-500">Stock: {{ format_unit_quantity($product->available_stock, $product->measurement_unit, auth()->user()->business_id) }}</p>
                        @endif
                    </button>
                </div>
            @empty
                <p class="col-span-full rounded-xl border border-dashed border-gray-200 bg-white p-8 text-center text-sm text-gray-500">No products available for sale.</p>
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
                    @if($customerCreditMode ?? false)
                        @if($waiterMode ?? false)
                            <option value="credit">Credit Tab (unpaid)</option>
                        @else
                            <option value="invoice">Invoice</option>
                            <option value="credit">Credit (on account)</option>
                        @endif
                    @endif
                </x-select>

                @if($waiterMode ?? false)
                <div id="mobileProviderWrap" class="hidden">
                    <x-select id="mobileMoneyProvider" label="Mobile provider">
                        <option value="mtn">MTN MoMo</option>
                        <option value="airtel">Airtel Money</option>
                    </x-select>
                </div>
                @endif

                <div id="customerSelectWrap" class="hidden space-y-2" @if(!($customerCreditMode ?? false)) data-credit-disabled="1" @endif>
                    <x-select id="customerId" label="{{ ($waiterMode ?? false) ? 'Customer (optional for tabs)' : 'Credit Customer' }}">
                        <option value="">{{ ($waiterMode ?? false) ? 'Walk-in / no customer' : 'Select customer' }}</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" data-phone="{{ $c->phone }}">{{ $c->name }} (Bal: @money($c->outstanding_balance))</option>
                        @endforeach
                    </x-select>
                    <button type="button" id="openNewCustomerBtn"
                            class="w-full rounded-lg border border-dashed border-indigo-300 px-3 py-2 text-xs font-semibold text-indigo-700 hover:border-indigo-400 hover:bg-indigo-50">
                        + New credit customer
                    </button>
                </div>

                </div>

                <div id="posActionSection" class="space-y-2 border-t border-gray-100 pt-2 sm:pt-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Total</span>
                        <span id="cartTotal" class="text-lg font-bold text-gray-900 sm:text-xl">{{ auth()->user()->business->formatMoney(0) }}</span>
                    </div>

                    @if($efrisCheckoutAvailable ?? false)
                        <label id="efrisCheckoutWrap" class="flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50/60 px-3 py-2 text-xs text-emerald-950">
                            <input type="checkbox" id="efrisRequested" value="1" class="mt-0.5 rounded border-emerald-300 text-emerald-600 focus:ring-emerald-500">
                            <span>Submit URA fiscal receipt (EFRIS) on this sale</span>
                        </label>
                    @endif

                @if($restaurantMode ?? false)
                    <x-button id="sendKitchenBtn" variant="success" size="lg" type="button" class="w-full min-h-[44px]" disabled>Send to Kitchen</x-button>
                    <button type="button" id="togglePayNowBtn" class="w-full text-center text-xs font-medium text-indigo-600 hover:text-indigo-800">Pay now (Counter sales)</button>
                @else
                <x-button id="checkoutBtn" variant="success" size="lg" type="button" class="js-checkout-submit w-full min-h-[44px]" disabled>Complete Sale</x-button>
                @endif
                @if($restaurantMode ?? false)
                    <x-button id="checkoutPayBtn" variant="secondary" size="lg" type="button" class="js-checkout-submit hidden w-full min-h-[44px]" disabled>Complete paid sale</x-button>
                @endif
                </div>
            </div>
        </x-card>
    </div>
</div>

<div id="invoiceCustomerModal" class="app-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="invoiceCustomerTitle">
    <div class="app-modal-panel mx-auto w-full max-w-md rounded-t-2xl bg-white p-5 shadow-xl sm:rounded-2xl">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <p id="invoiceCustomerTitle" class="text-lg font-bold text-gray-900">Invoice customer</p>
                <p class="text-sm text-gray-500">Enter billing details. Matching phone numbers reuse the existing customer.</p>
            </div>
            <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('invoiceCustomerModal')" aria-label="Close">&times;</button>
        </div>
        <form id="invoiceCustomerForm" class="space-y-3">
            <div>
                <label for="invoiceExistingCustomerId" class="mb-1 block text-xs font-medium text-gray-700">Existing customer (optional)</label>
                <select id="invoiceExistingCustomerId"
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">New customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" data-name="{{ $c->name }}" data-phone="{{ $c->phone }}">{{ $c->name }}@if($c->phone) · {{ $c->phone }}@endif</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="invoiceCustomerName" class="mb-1 block text-xs font-medium text-gray-700">Customer name <span class="text-red-600">*</span></label>
                <input type="text" id="invoiceCustomerName" required maxlength="255"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="invoiceCustomerPhone" class="mb-1 block text-xs font-medium text-gray-700">Phone / WhatsApp (optional)</label>
                <input type="tel" id="invoiceCustomerPhone" maxlength="30" placeholder="e.g. 0700123456"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <p id="invoiceCustomerError" class="hidden text-xs text-red-600"></p>
            <div class="grid grid-cols-2 gap-2 pt-2">
                <button type="button" onclick="closeAppModal('invoiceCustomerModal')"
                        class="min-h-[44px] rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="invoiceCustomerSubmitBtn"
                        class="min-h-[44px] rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
                    Create invoice
                </button>
            </div>
        </form>
    </div>
</div>

<div id="newCustomerModal" class="app-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="newCustomerTitle">
    <div class="app-modal-panel mx-auto w-full max-w-md rounded-t-2xl bg-white p-5 shadow-xl sm:rounded-2xl">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <p id="newCustomerTitle" class="text-lg font-bold text-gray-900">New credit customer</p>
                <p class="text-sm text-gray-500">Save and assign to this credit sale. Duplicate phones reuse the existing customer.</p>
            </div>
            <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('newCustomerModal')" aria-label="Close">&times;</button>
        </div>
        <form id="newCustomerForm" class="space-y-3">
            <div>
                <label for="newCustomerName" class="mb-1 block text-xs font-medium text-gray-700">Full name</label>
                <input type="text" id="newCustomerName" required maxlength="255"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label for="newCustomerPhone" class="mb-1 block text-xs font-medium text-gray-700">Phone</label>
                <input type="tel" id="newCustomerPhone" required maxlength="30" placeholder="e.g. 0700123456"
                       class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label for="newCustomerCreditLimit" class="mb-1 block text-xs font-medium text-gray-700">Credit limit</label>
                    <input type="number" id="newCustomerCreditLimit" min="0" step="0.01" value="0"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="newCustomerTerms" class="mb-1 block text-xs font-medium text-gray-700">Terms (days)</label>
                    <input type="number" id="newCustomerTerms" min="1" max="365" step="1" value="30"
                           class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>
            <p id="newCustomerError" class="hidden text-xs text-red-600"></p>
            <div class="grid grid-cols-2 gap-2 pt-2">
                <button type="button" onclick="closeAppModal('newCustomerModal')"
                        class="min-h-[44px] rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" id="saveNewCustomerBtn"
                        class="min-h-[44px] rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                    Save customer
                </button>
            </div>
        </form>
    </div>
</div>

<div id="saleReceiptModal" class="app-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="saleReceiptTitle">
    <div class="app-modal-panel mx-auto w-full max-w-md rounded-t-2xl bg-white p-5 shadow-xl sm:rounded-2xl">
        <div class="mb-1 flex items-start justify-between gap-3">
            <div>
                <p id="saleReceiptTitle" class="text-lg font-bold text-gray-900">Sale complete</p>
                <p id="saleReceiptNumber" class="text-sm text-gray-500"></p>
            </div>
            <button type="button" class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 hover:text-gray-600" onclick="closeAppModal('saleReceiptModal')" aria-label="Close">&times;</button>
        </div>
        <p id="saleReceiptSubtitle" class="mt-3 text-sm text-gray-600">Send an e-receipt to the customer or print a copy.</p>
        <div id="receiptPhoneWrap" class="mt-4">
            <label for="receiptCustomerPhone" class="mb-1 block text-xs font-medium text-gray-700">Customer WhatsApp (optional)</label>
            <input type="tel" id="receiptCustomerPhone" placeholder="e.g. 0700123456"
                   class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div id="receiptShareToolbar" class="mt-4 flex flex-wrap items-center gap-2">
            <button type="button" id="receiptPrintBtn"
                    class="inline-flex shrink-0 items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50">
                Print
            </button>
            <details id="receiptPrintMenu" class="relative hidden">
                <summary class="inline-flex shrink-0 cursor-pointer list-none items-center justify-center rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 [&::-webkit-details-marker]:hidden">
                    Print
                </summary>
                <div class="absolute left-0 z-30 mt-1 min-w-[9.5rem] rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                    <button type="button" id="receiptPrintInvoiceBtn"
                            class="block w-full px-3 py-2 text-left text-xs font-medium text-amber-900 hover:bg-amber-50">
                        Print invoice
                    </button>
                    <button type="button" id="receiptPrintReceiptBtn"
                            class="block w-full px-3 py-2 text-left text-xs font-medium text-gray-800 hover:bg-gray-50">
                        Print receipt
                    </button>
                </div>
            </details>
            <details id="receiptShareMenu" class="relative">
                <summary class="inline-flex shrink-0 cursor-pointer list-none items-center justify-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white shadow-sm hover:bg-indigo-700 [&::-webkit-details-marker]:hidden">
                    Share
                </summary>
                <div class="absolute left-0 z-30 mt-1 min-w-[9.5rem] rounded-lg border border-gray-200 bg-white py-1 shadow-lg">
                    <a id="receiptWhatsAppBtn" href="#" target="_blank" rel="noopener noreferrer"
                       class="block px-3 py-2 text-xs font-medium text-[#128C7E] hover:bg-emerald-50">
                        WhatsApp
                    </a>
                    <a id="receiptEmailBtn" href="#" target="_blank" rel="noopener noreferrer"
                       class="block px-3 py-2 text-xs font-medium text-sky-800 hover:bg-sky-50">
                        Email
                    </a>
                </div>
            </details>
        </div>
        <button type="button" id="receiptDoneBtn"
                class="mt-3 w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            Back
        </button>
    </div>
</div>
@endsection

@push('scripts')
@if($posOfflineEnabled ?? false)
<script src="{{ asset('js/pos-offline-store.js') }}"></script>
<script src="{{ asset('js/pos-offline-sync.js') }}"></script>
@endif
<script id="pos-catalog-data" type="application/json">@json($posCatalog)</script>
<script>
(function () {
    var csrf = document.querySelector('meta[name="csrf-token"]').content;
    var posOfflineEnabled = @json($posOfflineEnabled ?? false);
    var businessId = @json(auth()->user()->business_id);
    var checkoutUrl = @json(tenant_route('tenant.pos.checkout'));
    var offlineSyncUrl = @json(tenant_route('tenant.pos.sync-offline'));
    var quickCustomerUrl = @json(tenant_route('tenant.pos.customers.quick'));
    var sendKitchenUrl = @json(tenant_route('tenant.pos.send-kitchen'));
    var waiterMode = @json($waiterMode ?? false);
    var restaurantMode = @json($restaurantMode ?? false);
    var useRestaurantTables = @json($useRestaurantTables ?? false);
    var variablePricingMode = @json($variablePricingMode ?? false);
    var divisibleProductsMode = @json($divisibleProductsMode ?? false);
    var currencySample = @json(auth()->user()->business->formatMoney(0));
    var POS_CATALOG = JSON.parse(document.getElementById('pos-catalog-data').textContent);
    var productById = {};
    POS_CATALOG.forEach(function (p) { productById[String(p.id)] = p; });

    var cart = [];
    var expandedIdx = null;

    function formatMoney(amount) {
        return currencySample.replace(/[\d,.]+/, Number(amount).toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 }));
    }

    var pendingReceipt = { url: '', invoiceUrl: '', receiptUrl: '', message: '', emailSubject: '', customerEmail: '', isInvoice: false, isPaired: false };
    var invoiceCustomerId = null;
    var checkoutInProgress = false;
    var pendingCheckoutIdempotencyKey = null;
    var checkoutRequestTimeoutMs = 90000;

    function getCheckoutSubmitButtons() {
        return document.querySelectorAll('.js-checkout-submit');
    }

    function generateCheckoutIdempotencyKey() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }

        return 'chk-' + Date.now().toString(36) + '-' + Math.random().toString(36).slice(2, 14);
    }

    function ensureCheckoutIdempotencyKey() {
        if (!pendingCheckoutIdempotencyKey) {
            pendingCheckoutIdempotencyKey = generateCheckoutIdempotencyKey();
        }

        return pendingCheckoutIdempotencyKey;
    }

    function clearCheckoutIdempotencyKey() {
        pendingCheckoutIdempotencyKey = null;
    }

    function cacheCheckoutButtonLabels() {
        getCheckoutSubmitButtons().forEach(function (btn) {
            btn.dataset.checkoutIdleLabel = btn.textContent.trim();
        });
    }

    function setCheckoutButtonsProcessing(isProcessing) {
        getCheckoutSubmitButtons().forEach(function (btn) {
            if (isProcessing) {
                if (!btn.dataset.checkoutIdleLabel) {
                    btn.dataset.checkoutIdleLabel = btn.textContent.trim();
                }
                btn.disabled = true;
                btn.setAttribute('aria-busy', 'true');
                btn.classList.add('pointer-events-none', 'opacity-80');
                btn.textContent = 'Processing…';
            } else {
                btn.removeAttribute('aria-busy');
                btn.classList.remove('pointer-events-none', 'opacity-80');
                if (btn.dataset.checkoutIdleLabel) {
                    btn.textContent = btn.dataset.checkoutIdleLabel;
                }
                btn.disabled = cart.length === 0;
            }
        });

        var sendBtn = document.getElementById('sendKitchenBtn');
        if (sendBtn) {
            sendBtn.disabled = isProcessing || cart.length === 0;
        }
    }

    function activateCheckoutLock() {
        if (checkoutInProgress) {
            return false;
        }

        checkoutInProgress = true;
        cacheCheckoutButtonLabels();
        setCheckoutButtonsProcessing(true);

        return true;
    }

    function releaseCheckoutLock() {
        checkoutInProgress = false;
        setCheckoutButtonsProcessing(false);
        if (!restaurantMode) {
            updateCheckoutButtonLabel();
        }
    }

    function whenOfflineReady(fn) {
        if (window.DukaProOfflineStore && window.DukaProOfflinePOS) {
            fn();
            return;
        }
        window.setTimeout(function () { whenOfflineReady(fn); }, 30);
    }

    if (posOfflineEnabled) {
        whenOfflineReady(function () {
            window.DukaProOfflineStore.saveCatalog(businessId, POS_CATALOG).catch(function () {});
            window.DukaProOfflinePOS.init({
                businessId: businessId,
                syncUrl: offlineSyncUrl,
                csrf: csrf,
                statusElementId: 'pos-network-status',
            });
        });
    }

    function isPosOffline() {
        return posOfflineEnabled && window.DukaProOfflinePOS && !window.DukaProOfflinePOS.isOnline();
    }

    function normalizeWhatsAppPhone(value) {
        var digits = (value || '').replace(/\D/g, '');
        if (digits.length === 9) return '256' + digits;
        if (digits.length === 10 && digits.charAt(0) === '0') return '256' + digits.slice(1);
        return digits;
    }

    function buildWhatsAppUrl(phone, message) {
        var digits = normalizeWhatsAppPhone(phone);
        var base = digits ? 'https://wa.me/' + digits : 'https://wa.me/';
        return base + '?text=' + encodeURIComponent(message);
    }

    function formatApiError(data, fallback) {
        if (data && data.errors) {
            return Object.values(data.errors).flat().join(', ');
        }

        return (data && data.message) ? data.message : fallback;
    }

    async function readApiResponse(res) {
        var text = await res.text();
        if (!text) {
            return { data: null, parseError: null };
        }
        try {
            return { data: JSON.parse(text), parseError: null };
        } catch (parseError) {
            return { data: null, parseError: parseError, raw: text };
        }
    }

    function htmlResponseHint(res) {
        if (res.status === 419) {
            return 'Your session expired (CSRF). Refresh the POS page and complete the sale again.';
        }
        if (res.status === 401 || res.status === 403) {
            return 'You were signed out or lack permission. Refresh the page and sign in again.';
        }
        if (res.status >= 500) {
            return 'Server error (HTTP ' + res.status + '). Check with support or try again shortly.';
        }

        return 'Unexpected server response (HTTP ' + res.status + '). Refresh the POS page and try again.';
    }

    function buildMailtoUrl(email, subject, body) {
        var query = 'subject=' + encodeURIComponent(subject || '') + '&body=' + encodeURIComponent(body || '');
        var to = (email || '').trim();
        return to ? 'mailto:' + to + '?' + query : 'mailto:?' + query;
    }

    function updateReceiptShareLinks() {
        var phoneEl = document.getElementById('receiptCustomerPhone');
        if (!pendingReceipt.message) return;
        var phone = phoneEl ? phoneEl.value : '';
        var whatsappBtn = document.getElementById('receiptWhatsAppBtn');
        var emailBtn = document.getElementById('receiptEmailBtn');
        if (whatsappBtn) whatsappBtn.href = buildWhatsAppUrl(phone, pendingReceipt.message);
        if (emailBtn) {
            emailBtn.href = buildMailtoUrl(
                pendingReceipt.customerEmail,
                pendingReceipt.emailSubject,
                pendingReceipt.message
            );
        }
    }

    function showReceiptModal(data) {
        pendingReceipt.invoiceUrl = data.invoice_url || '';
        pendingReceipt.receiptUrl = data.receipt_url || '';
        pendingReceipt.url = pendingReceipt.invoiceUrl || pendingReceipt.receiptUrl || '';
        pendingReceipt.message = data.receipt_message || '';
        pendingReceipt.emailSubject = data.email_subject || '';
        pendingReceipt.customerEmail = data.customer_email || '';
        pendingReceipt.isPaired = !!(data.is_paired_documents || data.document_type === 'invoice_pair');
        pendingReceipt.isInvoice = pendingReceipt.isPaired || data.document_type === 'invoice';

        var isPaired = pendingReceipt.isPaired;
        var isInvoiceOnly = !isPaired && data.document_type === 'invoice';

        document.getElementById('saleReceiptTitle').textContent = isPaired
            ? 'Invoice & receipt ready'
            : (isInvoiceOnly ? 'Invoice ready' : 'Sale complete');
        document.getElementById('saleReceiptNumber').textContent = data.sale && data.sale.sale_number
            ? '#' + data.sale.sale_number
            : '';
        document.getElementById('saleReceiptSubtitle').textContent = isPaired
            ? 'Print the invoice or receipt, or send both to the customer on WhatsApp.'
            : (isInvoiceOnly
                ? 'Print the invoice or send it to the customer on WhatsApp.'
                : 'Send an e-receipt to the customer or print a copy.');

        var printBtn = document.getElementById('receiptPrintBtn');
        var printMenu = document.getElementById('receiptPrintMenu');
        var shareToolbar = document.getElementById('receiptShareToolbar');

        if (isPaired) {
            printBtn.classList.add('hidden');
            printMenu.classList.remove('hidden');
        } else {
            printBtn.classList.remove('hidden');
            printMenu.classList.add('hidden');
            printBtn.textContent = isInvoiceOnly ? 'Print invoice' : 'Print receipt';
        }
        if (shareToolbar) shareToolbar.classList.remove('hidden');

        var phoneEl = document.getElementById('receiptCustomerPhone');
        var phoneWrap = document.getElementById('receiptPhoneWrap');
        var customerSelect = document.getElementById('customerId');
        var phone = data.customer_phone || '';
        if (!phone && customerSelect && customerSelect.value) {
            var selected = customerSelect.options[customerSelect.selectedIndex];
            phone = selected && selected.dataset.phone ? selected.dataset.phone : '';
        }
        phoneEl.value = phone || '';
        phoneWrap.classList.remove('hidden');
        updateReceiptShareLinks();
        openAppModal('saleReceiptModal');
        if (!phone && (isPaired || isInvoiceOnly)) {
            phoneEl.focus();
        }
    }

    function showOfflineReceiptModal(localId, total, lineCount) {
        pendingReceipt.invoiceUrl = '';
        pendingReceipt.receiptUrl = '';
        pendingReceipt.url = '';
        pendingReceipt.message = 'Offline sale saved locally (' + localId + '). It will sync when you are back online.';
        pendingReceipt.isPaired = false;
        pendingReceipt.isInvoice = false;

        document.getElementById('saleReceiptTitle').textContent = 'Offline sale saved';
        document.getElementById('saleReceiptNumber').textContent = localId;
        document.getElementById('saleReceiptSubtitle').textContent =
            lineCount + ' item(s) · ' + formatMoney(total) + ' — queued for automatic sync when connectivity returns.';

        document.getElementById('receiptShareToolbar').classList.add('hidden');
        document.getElementById('receiptPhoneWrap').classList.add('hidden');

        openAppModal('saleReceiptModal');
    }

    document.getElementById('receiptCustomerPhone').addEventListener('input', updateReceiptShareLinks);
    document.getElementById('receiptPrintBtn').addEventListener('click', function () {
        if (pendingReceipt.url) window.open(pendingReceipt.url, '_blank');
    });
    document.getElementById('receiptPrintInvoiceBtn').addEventListener('click', function () {
        if (pendingReceipt.invoiceUrl) window.open(pendingReceipt.invoiceUrl, '_blank');
    });
    document.getElementById('receiptPrintReceiptBtn').addEventListener('click', function () {
        if (pendingReceipt.receiptUrl) window.open(pendingReceipt.receiptUrl, '_blank');
    });
    document.getElementById('receiptDoneBtn').addEventListener('click', function () {
        closeAppModal('saleReceiptModal');
        invoiceCustomerId = null;
        location.reload();
    });

    function updateCheckoutButtonLabel() {
        var paymentMethod = document.getElementById('paymentMethod').value;
        var checkoutBtn = document.getElementById('checkoutBtn');
        if (!checkoutBtn || restaurantMode) return;
        if (paymentMethod === 'invoice') {
            checkoutBtn.textContent = 'Invoice customer…';
        } else if (paymentMethod === 'credit') {
            checkoutBtn.textContent = 'Complete credit sale';
        } else {
            checkoutBtn.textContent = 'Complete Sale';
        }
    }

    function openInvoiceCustomerModal() {
        document.getElementById('invoiceCustomerError').classList.add('hidden');
        document.getElementById('invoiceCustomerError').textContent = '';
        var existingSelect = document.getElementById('invoiceExistingCustomerId');
        if (invoiceCustomerId && existingSelect) {
            existingSelect.value = String(invoiceCustomerId);
        } else if (existingSelect) {
            existingSelect.value = '';
        }
        syncInvoiceCustomerFieldsFromSelect();
        openAppModal('invoiceCustomerModal');
        document.getElementById('invoiceCustomerName').focus();
    }

    function syncInvoiceCustomerFieldsFromSelect() {
        var existingSelect = document.getElementById('invoiceExistingCustomerId');
        var nameEl = document.getElementById('invoiceCustomerName');
        var phoneEl = document.getElementById('invoiceCustomerPhone');
        if (!existingSelect || !existingSelect.value) {
            return;
        }
        var option = existingSelect.options[existingSelect.selectedIndex];
        if (option && option.dataset.name) {
            nameEl.value = option.dataset.name;
            phoneEl.value = option.dataset.phone || '';
        }
    }

    document.getElementById('invoiceExistingCustomerId').addEventListener('change', syncInvoiceCustomerFieldsFromSelect);

    function parseQty(val) {
        if (!divisibleProductsMode) {
            var whole = parseInt(String(val), 10);
            return isNaN(whole) ? 0 : Math.max(0, whole);
        }
        var qty = parseFloat(String(val).replace(',', '.'));
        if (isNaN(qty)) return 0;
        return Math.round(qty * 1000) / 1000;
    }

    function qtyInputAttrs(item) {
        if (item.is_service) {
            if (divisibleProductsMode) {
                return { min: 0.001, step: 0.001, max: null };
            }
            return { min: 1, step: 1, max: null };
        }
        if (divisibleProductsMode) {
            return {
                min: 0.001,
                step: 0.001,
                max: item.max_stock,
            };
        }
        return {
            min: 1,
            step: 1,
            max: Math.floor(item.max_stock),
        };
    }

    function isUnlimitedStock(item) {
        return !!(item && item.is_service);
    }

    function defaultUnit(product) {
        if (!product.units || !product.units.length) {
            return {
                id: null,
                name: product.measurement_unit || 'unit',
                factor: 1,
                price: parseFloat(product.price),
                max_stock: parseFloat(product.base_stock),
            };
        }
        return product.units.find(function (u) { return u.is_base; }) || product.units[0];
    }

    function unitById(product, unitId) {
        if (!product.units || !product.units.length) return defaultUnit(product);
        var match = product.units.find(function (u) { return String(u.id) === String(unitId); });
        return match || defaultUnit(product);
    }

    function formatQty(qty) {
        var n = parseQty(qty);
        if (!divisibleProductsMode) return String(n);
        if (Math.abs(n - Math.round(n)) < 0.0001) return String(Math.round(n));
        return n.toFixed(3).replace(/\.?0+$/, '');
    }

    function normalizeMaxStock(stock) {
        var n = parseQty(stock);
        return divisibleProductsMode ? n : Math.floor(n);
    }

    function addToCart(product, qty, unitId) {
        qty = parseQty(qty);
        if (!product || qty <= 0) return false;

        var unit = unitById(product, unitId);
        var maxStock = normalizeMaxStock(unit.max_stock);
        var existing = cart.find(function (i) {
            if (i.product_id !== product.id) return false;
            if (String(i.product_unit_id || '') !== String(unit.id || '')) return false;
            return restaurantMode ? lineKey(i) === lineKey({ product_id: product.id, product_unit_id: unit.id, notes: '' }) : true;
        });
        var nextQty = existing ? parseQty(existing.quantity + qty) : qty;

        if (!product.is_service && nextQty > maxStock) {
            alert('Insufficient stock for ' + product.name + '. Available: ' + formatQty(maxStock) + ' ' + unit.name);
            return false;
        }

        var targetIdx;
        if (existing) {
            existing.quantity = nextQty;
            targetIdx = cart.indexOf(existing);
        } else {
            cart.push({
                product_id: product.id,
                product_unit_id: unit.id,
                unit_name: unit.name,
                units: product.units || [],
                name: product.name,
                unit_price: parseFloat(unit.price),
                base_price: parseFloat(product.base_price || product.price),
                quantity: qty,
                max_stock: product.is_service ? 999999 : maxStock,
                is_service: !!product.is_service,
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
        return String(item.product_id) + '|' + String(item.product_unit_id || '') + '|' + (item.notes || '').trim();
    }

    function stockHintHtml(item) {
        if (isUnlimitedStock(item)) {
            return '<p class="text-xs text-violet-600">Service · adjust price or add a note below</p>';
        }
        return item.unit_name
            ? '<p class="text-xs text-gray-500">Sold by ' + esc(item.unit_name) + ' · max ' + formatQty(item.max_stock) + '</p>'
            : '<p class="text-xs text-gray-500">max ' + formatQty(item.max_stock) + '</p>';
    }

    function unitSelectorHtml(item, idx) {
        if (!item.units || item.units.length <= 1) {
            return stockHintHtml(item);
        }
        var options = item.units.map(function (u) {
            var selected = String(u.id) === String(item.product_unit_id) ? ' selected' : '';
            var suffix = isUnlimitedStock(item) ? '' : ' (max ' + formatQty(u.max_stock) + ')';
            return '<option value="' + u.id + '"' + selected + '>' + esc(u.name) + suffix + '</option>';
        }).join('');
        return '<label class="mt-1 block text-xs text-gray-500">Unit' +
            '<select data-action="unit" data-idx="' + idx + '" class="mt-0.5 w-full min-h-[36px] rounded-lg border border-gray-200 bg-white px-2 py-1 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">' +
            options +
            '</select></label>';
    }

    function priceFieldHtml(item, idx) {
        if (variablePricingMode || isUnlimitedStock(item)) {
            return '<label class="mt-1 block text-xs text-gray-500">' +
                'Unit price' +
                '<input type="number" min="0" step="0.01" value="' + item.unit_price + '" data-action="price" data-idx="' + idx + '" ' +
                'class="mt-0.5 w-full min-h-[36px] rounded-lg border border-amber-200 bg-amber-50/40 px-2 py-1 text-sm font-semibold text-gray-900 focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400">' +
                (item.base_price && item.base_price !== item.unit_price
                    ? '<span class="mt-0.5 block font-normal text-gray-400">Catalog: ' + formatMoney(item.base_price) + '</span>'
                    : '') +
            '</label>';
        }

        return '<p class="text-xs text-gray-500">' + formatMoney(item.unit_price) + ' / ' + esc(item.unit_name || 'unit') + ' · max ' + formatQty(item.max_stock) + '</p>';
    }

    function lineNotesHtml(item, idx) {
        if (!restaurantMode && !isUnlimitedStock(item)) {
            return '';
        }
        return '<input type="text" data-action="notes" data-idx="' + idx + '" value="' + esc(item.notes || '') + '" placeholder="Line note (optional)" maxlength="500" class="mt-2 w-full rounded-lg border border-gray-200 px-2 py-1.5 text-xs focus:border-emerald-500 focus:outline-none">';
    }

    function updateCartTotals() {
        var total = cart.reduce(function (s, i) { return s + i.quantity * i.unit_price; }, 0);
        var units = cart.reduce(function (s, i) { return s + i.quantity; }, 0);
        document.getElementById('cartTotal').textContent = formatMoney(total);
        document.getElementById('cartCount').textContent = String(units);
        return { total: total, units: units, hasItems: cart.length > 0 };
    }

    function renderCart() {
        if (!checkoutInProgress) {
            clearCheckoutIdempotencyKey();
        }

        var wrap = document.getElementById('cartItems');
        var totals = updateCartTotals();
        var hasItems = totals.hasItems;
        if (!checkoutInProgress) {
            getCheckoutSubmitButtons().forEach(function (btn) {
                btn.disabled = !hasItems;
            });
        }
        var sendBtn = document.getElementById('sendKitchenBtn');
        if (sendBtn && !checkoutInProgress) {
            sendBtn.disabled = !hasItems;
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
            var qtyAttrs = qtyInputAttrs(item);

            if (isExpanded) {
                return '<div class="border-b border-gray-100 py-3 last:border-0" data-idx="' + idx + '">' +
                    '<div class="flex items-start justify-between gap-2">' +
                        '<div class="min-w-0 flex-1">' +
                            '<p class="truncate text-sm font-medium text-gray-900">' + esc(item.name) + '</p>' +
                            unitSelectorHtml(item, idx) +
                            priceFieldHtml(item, idx) +
                        '</div>' +
                        (restaurantMode
                            ? '<button type="button" data-action="collapse" data-idx="' + idx + '" class="shrink-0 text-xs font-medium text-gray-500 hover:text-gray-700">Done</button>'
                            : '<button type="button" data-action="remove" data-idx="' + idx + '" class="min-h-[36px] min-w-[36px] shrink-0 rounded-lg border border-red-200 text-sm text-red-600 hover:bg-red-50" aria-label="Remove">×</button>') +
                    '</div>' +
                    lineNotesHtml(item, idx) +
                    '<div class="mt-2 flex items-center gap-2">' +
                        '<button type="button" data-action="minus" data-idx="' + idx + '" class="min-h-[40px] min-w-[40px] rounded-lg border border-gray-300 text-sm hover:bg-gray-50">−</button>' +
                        '<input type="number" min="' + qtyAttrs.min + '" step="' + qtyAttrs.step + '"' + (qtyAttrs.max != null ? ' max="' + qtyAttrs.max + '"' : '') + ' value="' + formatQty(item.quantity) + '" data-action="qty" data-idx="' + idx + '" ' +
                            'class="w-24 min-h-[40px] rounded-lg border-gray-300 text-center text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">' +
                        '<span class="text-xs text-gray-500">' + esc(item.unit_name || '') + '</span>' +
                        '<button type="button" data-action="plus" data-idx="' + idx + '" class="min-h-[40px] min-w-[40px] rounded-lg border border-gray-300 text-sm hover:bg-gray-50">+</button>' +
                        '<span class="ml-auto text-sm font-semibold text-gray-900">' + formatMoney(lineTotal) + '</span>' +
                    '</div>' +
                    (restaurantMode ? '<button type="button" data-action="remove" data-idx="' + idx + '" class="mt-2 text-xs font-medium text-red-600 hover:text-red-700">Remove item</button>' : '') +
                '</div>';
            }

            return '<button type="button" data-action="expand" data-idx="' + idx + '" class="flex w-full items-start justify-between gap-2 border-b border-gray-100 py-3 text-left last:border-0 hover:bg-gray-50/80">' +
                '<div class="min-w-0 flex-1">' +
                    '<p class="truncate text-sm font-medium text-gray-900">' + formatQty(item.quantity) + ' ' + esc(item.unit_name || '') + ' · ' + esc(item.name) + '</p>' +
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
        var step = 1;
        if (divisibleProductsMode && item.unit_name && item.unit_name !== 'piece') {
            step = 0.5;
        }
        setQty(idx, parseQty(item.quantity + (delta > 0 ? step : -step)));
    }

    function setCartUnit(idx, unitId) {
        var item = cart[idx];
        if (!item || !item.units || !item.units.length) return;

        var unit = item.units.find(function (u) { return String(u.id) === String(unitId); });
        if (!unit) return;

        var duplicate = cart.findIndex(function (row, i) {
            return i !== idx
                && row.product_id === item.product_id
                && String(row.product_unit_id || '') === String(unit.id || '')
                && (!restaurantMode || lineKey(row) === lineKey({ product_id: item.product_id, product_unit_id: unit.id, notes: item.notes }));
        });

        item.product_unit_id = unit.id;
        item.unit_name = unit.name;
        item.max_stock = normalizeMaxStock(unit.max_stock);
        if (!variablePricingMode) {
            item.unit_price = parseFloat(unit.price);
        }
        if (item.quantity > item.max_stock) {
            item.quantity = item.max_stock;
        }

        if (duplicate >= 0) {
            cart[duplicate].quantity = parseQty(cart[duplicate].quantity + item.quantity);
            cart.splice(idx, 1);
            expandedIdx = duplicate;
        }

        renderCart();
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
        if (!isUnlimitedStock(item) && qty > item.max_stock) {
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
        var priceInput = e.target.closest('[data-action="price"]');
        if (priceInput) {
            var priceIdx = parseInt(priceInput.getAttribute('data-idx'), 10);
            var item = cart[priceIdx];
            if (!item) return;
            var price = parseFloat(priceInput.value);
            if (!isNaN(price) && price >= 0) {
                item.unit_price = Math.round(price * 100) / 100;
                updateCartTotals();
            }
            return;
        }

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
        var unitSelect = e.target.closest('[data-action="unit"]');
        if (unitSelect) {
            setCartUnit(parseInt(unitSelect.getAttribute('data-idx'), 10), unitSelect.value);
            return;
        }
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

    function appendCustomerOption(customer) {
        var select = document.getElementById('customerId');
        if (!select) return;
        var existing = select.querySelector('option[value="' + customer.id + '"]');
        var label = customer.name + ' (Bal: ' + formatMoney(customer.outstanding_balance || 0) + ')';
        if (existing) {
            existing.textContent = label;
            existing.dataset.phone = customer.phone || '';
            select.value = String(customer.id);
            return;
        }
        var option = document.createElement('option');
        option.value = customer.id;
        option.dataset.phone = customer.phone || '';
        option.textContent = label;
        select.appendChild(option);
        select.value = String(customer.id);
    }

    function openNewCustomerModal() {
        document.getElementById('newCustomerError').classList.add('hidden');
        document.getElementById('newCustomerError').textContent = '';
        openAppModal('newCustomerModal');
        document.getElementById('newCustomerName').focus();
    }

    document.getElementById('openNewCustomerBtn').addEventListener('click', openNewCustomerModal);

    document.getElementById('newCustomerForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        var saveBtn = document.getElementById('saveNewCustomerBtn');
        var errorEl = document.getElementById('newCustomerError');
        errorEl.classList.add('hidden');
        saveBtn.disabled = true;
        try {
            var res = await fetch(quickCustomerUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    name: document.getElementById('newCustomerName').value.trim(),
                    phone: document.getElementById('newCustomerPhone').value.trim(),
                    credit_limit: parseFloat(document.getElementById('newCustomerCreditLimit').value) || 0,
                    payment_terms_days: parseInt(document.getElementById('newCustomerTerms').value, 10) || 30,
                }),
            });
            var data = await res.json();
            if (!res.ok) {
                throw new Error(formatApiError(data, 'Could not save customer'));
            }
            appendCustomerOption(data.customer);
            closeAppModal('newCustomerModal');
            if (!data.created) {
                alert('Existing customer matched by phone number and selected for this sale.');
            }
        } catch (err) {
            errorEl.textContent = err.message;
            errorEl.classList.remove('hidden');
        } finally {
            saveBtn.disabled = false;
        }
    });

    syncEfrisCheckoutOption();

    document.getElementById('paymentMethod').addEventListener('change', function () {
        var method = this.value;
        document.getElementById('customerSelectWrap').classList.toggle('hidden', method !== 'credit');
        if (method === 'invoice') {
            invoiceCustomerId = null;
        } else if (method !== 'credit') {
            invoiceCustomerId = null;
        }
        if (waiterMode) {
            var mobileWrap = document.getElementById('mobileProviderWrap');
            if (mobileWrap) {
                mobileWrap.classList.toggle('hidden', method !== 'mobile_money');
            }
        }
        updateCheckoutButtonLabel();
        syncEfrisCheckoutOption();
    });

    document.getElementById('invoiceCustomerForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        if (checkoutInProgress) return;

        var submitBtn = document.getElementById('invoiceCustomerSubmitBtn');
        var errorEl = document.getElementById('invoiceCustomerError');
        var existingSelect = document.getElementById('invoiceExistingCustomerId');
        errorEl.classList.add('hidden');

        if (existingSelect && existingSelect.value) {
            invoiceCustomerId = parseInt(existingSelect.value, 10);
            closeAppModal('invoiceCustomerModal');
            await processCheckout();
            return;
        }

        submitBtn.disabled = true;
        try {
            var res = await fetch(quickCustomerUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    name: document.getElementById('invoiceCustomerName').value.trim(),
                    phone: document.getElementById('invoiceCustomerPhone').value.trim() || null,
                    for_invoice: true,
                }),
            });
            var data = await res.json();
            if (!res.ok) {
                throw new Error(formatApiError(data, 'Could not save customer'));
            }
            appendCustomerOption(data.customer);
            appendInvoiceCustomerOption(data.customer);
            invoiceCustomerId = data.customer.id;
            closeAppModal('invoiceCustomerModal');
            if (!data.created && data.customer.phone) {
                alert('Existing customer matched by phone number.');
            }
            await processCheckout();
        } catch (err) {
            errorEl.textContent = err.message;
            errorEl.classList.remove('hidden');
        } finally {
            submitBtn.disabled = false;
        }
    });

    function appendInvoiceCustomerOption(customer) {
        var select = document.getElementById('invoiceExistingCustomerId');
        if (!select) return;
        var existing = select.querySelector('option[value="' + customer.id + '"]');
        var label = customer.name + (customer.phone ? ' · ' + customer.phone : '');
        if (existing) {
            existing.textContent = label;
            existing.dataset.name = customer.name;
            existing.dataset.phone = customer.phone || '';
            return;
        }
        var option = document.createElement('option');
        option.value = customer.id;
        option.dataset.name = customer.name;
        option.dataset.phone = customer.phone || '';
        option.textContent = label;
        select.appendChild(option);
    }

    if (restaurantMode) {
        document.getElementById('togglePayNowBtn').addEventListener('click', function () {
            var paymentSection = document.getElementById('posPaymentSection');
            var checkoutPayBtn = document.getElementById('checkoutPayBtn');
            var sendBtn = document.getElementById('sendKitchenBtn');
            var showingPay = !paymentSection.classList.contains('hidden');
            paymentSection.classList.toggle('hidden', showingPay);
            if (checkoutPayBtn) {
                checkoutPayBtn.classList.toggle('hidden', showingPay);
                if (!checkoutInProgress) {
                    checkoutPayBtn.disabled = cart.length === 0;
                }
            }
            sendBtn.classList.toggle('hidden', !showingPay);
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
                            return {
                                product_id: i.product_id,
                                product_unit_id: i.product_unit_id || null,
                                quantity: i.quantity,
                                notes: i.notes || null,
                            };
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

    function buildCheckoutPayload(paymentMethod, resolvedCustomerId, waiterId, mobileProvider, tablePayload) {
        return {
            items: cart.map(function (i) {
                return {
                    product_id: i.product_id,
                    product_unit_id: i.product_unit_id || null,
                    quantity: i.quantity,
                    unit_price: i.unit_price,
                    notes: i.notes || null,
                };
            }),
            payment_method: paymentMethod,
            mobile_money_provider: paymentMethod === 'mobile_money' ? mobileProvider : null,
            customer_id: resolvedCustomerId,
            waiter_id: waiterId || null,
            is_credit_sale: paymentMethod === 'credit' || paymentMethod === 'invoice',
            notes: restaurantMode ? (document.getElementById('orderNotes').value.trim() || null) : null,
            table_label: tablePayload.table_label || null,
            restaurant_table_id: tablePayload.restaurant_table_id || null,
            efris_requested: isEfrisRequestedForCheckout(paymentMethod),
        };
    }

    function isEfrisRequestedForCheckout(paymentMethod) {
        var box = document.getElementById('efrisRequested');
        if (!box || box.disabled || !box.checked) {
            return false;
        }
        if (paymentMethod === 'credit' || paymentMethod === 'invoice') {
            return false;
        }
        return true;
    }

    function syncEfrisCheckoutOption() {
        var wrap = document.getElementById('efrisCheckoutWrap');
        var box = document.getElementById('efrisRequested');
        if (!wrap || !box) return;
        var paymentMethod = document.getElementById('paymentMethod').value;
        var creditLike = paymentMethod === 'credit' || paymentMethod === 'invoice';
        wrap.classList.toggle('hidden', creditLike);
        if (creditLike) {
            box.checked = false;
            box.disabled = true;
        } else {
            box.disabled = false;
        }
    }

    async function processOfflineCheckout(checkoutPayload, totals) {
        if (!window.DukaProOfflineStore) {
            throw new Error('Offline storage is not ready.');
        }

        var lineCount = cart.length;
        var localId = window.DukaProOfflineStore.generateLocalId();
        await window.DukaProOfflineStore.queuePendingSale({
            local_id: localId,
            businessId: businessId,
            payload: checkoutPayload,
            synced: false,
            createdAt: Date.now(),
        });

        cart = [];
        expandedIdx = null;
        invoiceCustomerId = null;
        renderCart();
        showOfflineReceiptModal(localId, totals.total, lineCount);
    }

    async function processCheckout() {
        if (!activateCheckoutLock()) {
            return;
        }

        try {
            var paymentMethod = document.getElementById('paymentMethod').value;
            var customerId = document.getElementById('customerId').value;
            var waiterId = waiterMode ? document.getElementById('waiterId').value : null;
            var mobileProviderEl = document.getElementById('mobileMoneyProvider');
            var mobileProvider = mobileProviderEl ? mobileProviderEl.value : null;

            if (!paymentMethod) {
                alert('Select a payment method');
                return;
            }
            if (waiterMode && !waiterId) {
                alert('Select the waiter or floor staff for this order');
                return;
            }
            if (paymentMethod === 'invoice' && !invoiceCustomerId) {
                openInvoiceCustomerModal();
                return;
            }
            if (paymentMethod === 'credit' && !waiterMode && !customerId) {
                openNewCustomerModal();
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

            if (isPosOffline()) {
                if (paymentMethod !== 'cash') {
                    alert('Offline mode supports cash sales only. Choose Cash or reconnect to use other payment methods.');
                    return;
                }
            } else if (!posOfflineEnabled && !navigator.onLine) {
                alert('You are offline and offline POS is disabled. Reconnect to complete this sale.');
                return;
            }

            var resolvedCustomerId = paymentMethod === 'invoice'
                ? invoiceCustomerId
                : (customerId || null);
            var checkoutPayload = buildCheckoutPayload(
                paymentMethod,
                resolvedCustomerId,
                waiterId,
                mobileProvider,
                tablePayload
            );
            var idempotencyKey = ensureCheckoutIdempotencyKey();
            checkoutPayload.idempotency_key = idempotencyKey;
            var totals = updateCartTotals();

            if (isPosOffline()) {
                await processOfflineCheckout(checkoutPayload, totals);
                clearCheckoutIdempotencyKey();
                return;
            }

            var abortController = typeof AbortController !== 'undefined' ? new AbortController() : null;
            var timeoutId = abortController
                ? window.setTimeout(function () { abortController.abort(); }, checkoutRequestTimeoutMs)
                : null;

            var res;
            try {
                res = await fetch(checkoutUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                        'Idempotency-Key': idempotencyKey,
                    },
                    body: JSON.stringify(checkoutPayload),
                    signal: abortController ? abortController.signal : undefined,
                });
            } catch (fetchErr) {
                if (fetchErr && fetchErr.name === 'AbortError') {
                    throw new Error('Checkout timed out. Check your connection and try again — you will not be double-charged for the same attempt.');
                }
                throw fetchErr;
            } finally {
                if (timeoutId) {
                    window.clearTimeout(timeoutId);
                }
            }
            var parsed = await readApiResponse(res);
            if (parsed.parseError) {
                throw new Error(htmlResponseHint(res));
            }
            var data = parsed.data;
            if (!res.ok) {
                throw new Error(formatApiError(data, 'Checkout failed'));
            }
            cart = [];
            expandedIdx = null;
            invoiceCustomerId = null;
            var efrisBox = document.getElementById('efrisRequested');
            if (efrisBox) {
                efrisBox.checked = false;
            }
            clearCheckoutIdempotencyKey();
            renderCart();
            showReceiptModal(data);
        } catch (err) {
            alert(err.message || 'Checkout failed. Please try again.');
        } finally {
            releaseCheckoutLock();
        }
    }

    getCheckoutSubmitButtons().forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (checkoutInProgress) {
                return;
            }
            processCheckout();
        });
    });

    updateCheckoutButtonLabel();
})();
</script>
@endpush
