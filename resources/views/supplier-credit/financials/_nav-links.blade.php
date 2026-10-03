@php
    $link = 'rounded-lg px-3 py-1.5 text-sm font-medium transition';
    $active = 'bg-indigo-100 text-indigo-800';
    $idle = 'text-gray-600 hover:bg-gray-100';
@endphp
<nav class="mb-6 flex flex-wrap gap-2" aria-label="Financial statements">
    <a href="{{ tenant_route('tenant.supplier-credit.financials.income-statement', request()->only(['period', 'from', 'to'])) }}"
       class="{{ $link }} {{ request()->routeIs('tenant.supplier-credit.financials.income-statement') ? $active : $idle }}">
        Income statement
    </a>
    <a href="{{ tenant_route('tenant.supplier-credit.financials.balance-sheet', request()->only(['period', 'from', 'to'])) }}"
       class="{{ $link }} {{ request()->routeIs('tenant.supplier-credit.financials.balance-sheet') ? $active : $idle }}">
        Balance sheet
    </a>
    <a href="{{ tenant_route('tenant.supplier-credit.overview.index') }}"
       class="{{ $link }} {{ request()->routeIs('tenant.supplier-credit.overview.*') ? $active : $idle }}">
        Purchases overview
    </a>
</nav>
