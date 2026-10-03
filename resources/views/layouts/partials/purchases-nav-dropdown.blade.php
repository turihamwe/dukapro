@php
    $purchasesOpen = request()->routeIs('tenant.supplier-credit.*');
    $isModern = ($theme ?? 'plain') === 'modern';
    $navLink = $navLink ?? 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition';
    $subActive = $isModern ? 'font-medium text-emerald-400 bg-white/10' : 'font-medium text-indigo-700 bg-indigo-50';
    $subIdle = $isModern ? 'text-slate-300 hover:bg-white/5 hover:text-white' : 'text-gray-600 hover:bg-gray-100';
    $containerClass = $isModern ? 'modern-purchases-nav' : 'purchases-nav';
    $btnClass = $isModern
        ? 'modern-nav-link flex w-full items-center justify-between gap-3 rounded-lg px-3 py-3 text-sm font-medium transition border-l-[3px]'
        : $navLink . ' w-full justify-between';
    $btnState = $purchasesOpen ? $navActive : $navIdle;
@endphp
<div class="{{ $containerClass }}">
    <button type="button"
            data-purchases-toggle
            aria-expanded="{{ $purchasesOpen ? 'true' : 'false' }}"
            class="{{ $btnClass }} {{ $btnState }}">
        <span class="flex items-center gap-3">
            @if($isModern)
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            @else
                <span>🧾</span>
            @endif
            Purchases
        </span>
        <svg data-purchases-chevron class="h-4 w-4 shrink-0 transition {{ $purchasesOpen ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div data-purchases-menu class="ml-4 mt-1 space-y-1 {{ $purchasesOpen ? '' : 'hidden' }}">
        <a href="{{ tenant_route('tenant.supplier-credit.receives.create') }}"
           title="Restock"
           class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs(['tenant.supplier-credit.receives.*', 'tenant.supplier-credit.purchases.create']) ? $subActive : $subIdle }}">Purchase Receives</a>
        <a href="{{ tenant_route('tenant.supplier-credit.bills.index') }}"
           class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('tenant.supplier-credit.bills.*') ? $subActive : $subIdle }}">Bills</a>
        <a href="{{ tenant_route('tenant.supplier-credit.payments.index') }}"
           class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('tenant.supplier-credit.payments.*') ? $subActive : $subIdle }}">Payments Made</a>
        <a href="{{ tenant_route('tenant.supplier-credit.vendors.index') }}"
           class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs(['tenant.supplier-credit.vendors.*', 'tenant.supplier-credit.suppliers.*']) ? $subActive : $subIdle }}">Vendors</a>
        <a href="{{ tenant_route('tenant.supplier-credit.overview.index') }}"
           class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('tenant.supplier-credit.overview.*') || request()->routeIs('tenant.supplier-credit.index') ? $subActive : $subIdle }}">Overview</a>
    </div>
</div>
