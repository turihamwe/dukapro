@can('view-dashboard')
    <a href="{{ tenant_route('tenant.dashboard') }}"
       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition border-l-[3px] {{ request()->routeIs('tenant.dashboard') ? $navActive : $navIdle }}">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
        Dashboard
    </a>
@endcan
@can('view-inventory')
    <a href="{{ tenant_route('tenant.inventory.index') }}"
       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition border-l-[3px] {{ request()->routeIs('tenant.inventory.*') ? $navActive : $navIdle }}">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        Inventory
    </a>
@endcan
@can('view-sales-reports')
    <a href="{{ tenant_route('tenant.reports.sales.index') }}"
       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition border-l-[3px] {{ request()->routeIs('tenant.reports.sales.*') ? $navActive : $navIdle }}">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Sales
    </a>
@endcan
@if(auth()->user()->can('view-sales-reports') || auth()->user()->can('view-all-reconciliations'))
    @php
        $reportsUrl = auth()->user()->can('view-all-reconciliations')
            ? tenant_route('tenant.reconciliation.index')
            : tenant_route('tenant.reports.sales.index');
        $reportsActive = request()->routeIs('tenant.reconciliation.*') || request()->routeIs('tenant.reports.*');
    @endphp
    <a href="{{ $reportsUrl }}"
       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition border-l-[3px] {{ $reportsActive ? $navActive : $navIdle }}">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Reports
    </a>
@endif
@can('view-inventory')
    <a href="{{ tenant_route('tenant.inventory.index', ['stock' => 'low']) }}"
       class="flex items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition border-l-[3px] {{ request()->get('stock') === 'low' ? $navActive : $navIdle }}">
        <span class="flex items-center gap-3">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Stock Alerts
        </span>
        @if($lowStockCount > 0)
            <span class="rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $lowStockCount > 99 ? '99+' : $lowStockCount }}</span>
        @endif
    </a>
@endcan
@can('manage-settings')
    <a href="{{ tenant_route('tenant.business.edit') }}"
       class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition border-l-[3px] {{ request()->routeIs('tenant.business.*') ? $navActive : $navIdle }}">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Settings
    </a>
@endcan
