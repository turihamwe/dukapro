@can('view-dashboard')
    <a href="{{ tenant_route('tenant.dashboard') }}"
       class="modern-nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition border-l-[3px] {{ request()->routeIs('tenant.dashboard') ? $navActive : $navIdle }}">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
        Dashboard
    </a>
@endcan
@can('view-inventory')
    <a href="{{ tenant_route('tenant.inventory.index') }}"
       class="modern-nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition border-l-[3px] {{ request()->routeIs('tenant.inventory.*') && ! request()->routeIs('tenant.brands.*') && request()->get('stock') !== 'low' ? $navActive : $navIdle }}">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        Inventory
    </a>
    {{-- <a href="{{ tenant_route('tenant.brands.index') }}"
       class="modern-nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition border-l-[3px] {{ request()->routeIs('tenant.brands.*') ? $navActive : $navIdle }}">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
        Brands
    </a> --}}
@endcan
@if(auth()->user()->can('view-sales-reports') || auth()->user()->can('view-all-reconciliations') || auth()->user()->can('view-expenses') || auth()->user()->can('view-reconciliation-shortages'))
    @php
        $reportsOpen = request()->routeIs('tenant.reports.*')
            || request()->routeIs('tenant.reconciliation.*')
            || request()->routeIs('tenant.reconciliation-shortages.*')
            || request()->routeIs('tenant.expenses.index');
    @endphp
    <div class="modern-reports-nav">
        <button type="button"
                data-reports-toggle
                aria-expanded="{{ $reportsOpen ? 'true' : 'false' }}"
                class="modern-nav-link flex w-full items-center justify-between gap-3 rounded-lg px-3 py-3 text-sm font-medium transition border-l-[3px] {{ $reportsOpen ? $navActive : $navIdle }}">
            <span class="flex items-center gap-3">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                Reports
            </span>
            <svg data-reports-chevron class="h-4 w-4 shrink-0 transition {{ $reportsOpen ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div data-reports-menu class="ml-4 mt-1 space-y-1 {{ $reportsOpen ? '' : 'hidden' }}">
            @can('view-sales-reports')
                <a href="{{ tenant_route('tenant.reports.sales.index') }}"
                   class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('tenant.reports.sales.*') ? 'font-medium text-emerald-400 bg-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">Sales reports</a>
            @endcan
            @can('view-all-reconciliations')
                <a href="{{ tenant_route('tenant.reconciliation.index') }}"
                   class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('tenant.reconciliation.*') && ! request()->routeIs('tenant.reconciliation-shortages.*') ? 'font-medium text-emerald-400 bg-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">EOD reports</a>
            @endcan
            @can('view-reconciliation-shortages')
                <a href="{{ tenant_route('tenant.reconciliation-shortages.index') }}"
                   class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('tenant.reconciliation-shortages.*') ? 'font-medium text-emerald-400 bg-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">Shift shortages</a>
            @endcan
            @can('view-expenses')
                <a href="{{ tenant_route('tenant.expenses.index') }}"
                   class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('tenant.expenses.index') ? 'font-medium text-emerald-400 bg-white/10' : 'text-slate-300 hover:bg-white/5 hover:text-white' }}">Expenses reports</a>
            @endcan
        </div>
    </div>
@endif
@can('manage-employees')
    <a href="{{ tenant_route('tenant.staff.index') }}"
       class="modern-nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition border-l-[3px] {{ request()->routeIs('tenant.staff.*') ? $navActive : $navIdle }}">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
        Staff
    </a>
@endcan
@can('view-inventory')
    <a href="{{ tenant_route('tenant.inventory.index', ['stock' => 'low']) }}"
       class="modern-nav-link flex items-center justify-between gap-3 rounded-lg px-3 py-3 text-sm font-medium transition border-l-[3px] {{ request()->get('stock') === 'low' ? $navActive : $navIdle }}">
        <span class="flex items-center gap-3">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            Stock Alerts
        </span>
        @if($lowStockCount > 0)
            <span class="rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $lowStockCount > 99 ? '99+' : $lowStockCount }}</span>
        @endif
    </a>
@endcan
@can('view-customers')
    <a href="{{ tenant_route('tenant.contacts.index') }}"
       class="modern-nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition border-l-[3px] {{ request()->routeIs('tenant.contacts.*') ? $navActive : $navIdle }}">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
        Contacts
    </a>
@endcan
@can('manage-branches')
    <a href="{{ tenant_route('tenant.branches.index') }}"
       class="modern-nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition border-l-[3px] {{ request()->routeIs('tenant.branches.*') ? $navActive : $navIdle }}">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        Branches
    </a>
@endcan
@can('manage-settings')
    <a href="{{ tenant_route('tenant.business.edit') }}"
       class="modern-nav-link flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition border-l-[3px] {{ request()->routeIs('tenant.business.*') ? $navActive : $navIdle }}">
        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Settings
    </a>
@endcan
