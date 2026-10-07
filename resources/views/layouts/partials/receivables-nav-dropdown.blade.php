@php
    $receivablesOpen = request()->routeIs('tenant.customer-credit.*');
    $isModern = ($theme ?? 'plain') === 'modern';
    $navLink = $navLink ?? 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition';
    $subActive = $isModern ? 'font-medium text-emerald-400 bg-white/10' : 'font-medium text-indigo-700 bg-indigo-50';
    $subIdle = $isModern ? 'text-slate-300 hover:bg-white/5 hover:text-white' : 'text-gray-600 hover:bg-gray-100';
    $containerClass = $isModern ? 'modern-receivables-nav' : 'receivables-nav';
    $btnClass = $isModern
        ? 'modern-nav-link flex w-full items-center justify-between gap-3 rounded-lg px-3 py-3 text-sm font-medium transition border-l-[3px]'
        : $navLink . ' w-full justify-between';
    $btnState = $receivablesOpen ? $navActive : $navIdle;
@endphp
<div class="{{ $containerClass }}">
    <button type="button"
            data-receivables-toggle
            aria-expanded="{{ $receivablesOpen ? 'true' : 'false' }}"
            class="{{ $btnClass }} {{ $btnState }}">
        <span class="flex items-center gap-3">
            @if($isModern)
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            @else
                <span>📋</span>
            @endif
            Receivables
        </span>
        <svg data-receivables-chevron class="h-4 w-4 shrink-0 transition {{ $receivablesOpen ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>
    <div data-receivables-menu class="ml-4 mt-1 space-y-1 {{ $receivablesOpen ? '' : 'hidden' }}">
        <a href="{{ tenant_route('tenant.customer-credit.customers.index') }}"
           class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('tenant.customer-credit.customers.*') ? $subActive : $subIdle }}">Customers</a>
        <a href="{{ tenant_route('tenant.customer-credit.invoices.index') }}"
           class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('tenant.customer-credit.invoices.*') ? $subActive : $subIdle }}">Invoices / Credit Sales</a>
        <a href="{{ tenant_route('tenant.customer-credit.payments.index') }}"
           class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('tenant.customer-credit.payments.*') ? $subActive : $subIdle }}">Payments Received</a>
    </div>
</div>
