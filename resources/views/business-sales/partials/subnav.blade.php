@php
    $subnavLink = 'rounded-lg px-3 py-2 text-sm font-medium transition';
    $subnavActive = 'bg-violet-100 text-violet-800';
    $subnavIdle = 'text-gray-600 hover:bg-gray-100 hover:text-gray-900';
@endphp
<nav class="mb-8 flex flex-wrap gap-2 border-b border-gray-200 pb-4">
    <a href="{{ route('superadmin.business-sales.index') }}"
       class="{{ $subnavLink }} {{ request()->routeIs('superadmin.business-sales.index') ? $subnavActive : $subnavIdle }}">
        Funnel &amp; projections
    </a>
    <a href="{{ route('superadmin.business-sales.engagement') }}"
       class="{{ $subnavLink }} {{ request()->routeIs('superadmin.business-sales.engagement') ? $subnavActive : $subnavIdle }}">
        Engagement activity
    </a>
</nav>
