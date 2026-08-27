@can('view-dashboard')
    <a href="{{ tenant_route('tenant.dashboard') }}"
       class="{{ $navLink }} {{ request()->routeIs('tenant.dashboard') ? $navActive : $navIdle }}">
        <span>📊</span> Dashboard
    </a>
@endcan
@can('view-inventory')
    <a href="{{ tenant_route('tenant.inventory.index') }}"
       class="{{ $navLink }} {{ request()->routeIs('tenant.inventory.*') ? $navActive : $navIdle }}">
        <span>📦</span> Inventory
    </a>
@endcan
@can('view-customers')
    <a href="{{ tenant_route('tenant.contacts.index') }}"
       class="{{ $navLink }} {{ request()->routeIs('tenant.contacts.*') ? $navActive : $navIdle }}">
        <span>👥</span> Contacts
    </a>
@endcan
@if(auth()->user()->can('view-sales-reports') || auth()->user()->can('view-all-reconciliations'))
    @php $reportsOpen = request()->routeIs('tenant.reports.*') || request()->routeIs('tenant.reconciliation.index'); @endphp
    <div class="reports-nav">
        <button type="button" id="{{ isset($mobile) && $mobile ? 'mobile-reports-toggle' : 'reports-toggle' }}"
                class="{{ $navLink }} w-full justify-between {{ $reportsOpen ? $navActive : $navIdle }}">
            <span class="flex items-center gap-3"><span>📈</span> Reports</span>
            <svg class="h-4 w-4 shrink-0 transition {{ $reportsOpen ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div id="{{ isset($mobile) && $mobile ? 'mobile-reports-menu' : 'reports-menu' }}" class="ml-4 mt-1 space-y-1 {{ $reportsOpen ? '' : 'hidden' }}">
            @can('view-sales-reports')
                <a href="{{ tenant_route('tenant.reports.sales.index') }}"
                   class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('tenant.reports.sales.*') ? 'font-medium text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:bg-gray-100' }}">Sales reports</a>
            @endcan
            @can('view-all-reconciliations')
                <a href="{{ tenant_route('tenant.reconciliation.index') }}"
                   class="block rounded-lg px-3 py-2 text-sm {{ request()->routeIs('tenant.reconciliation.index') ? 'font-medium text-indigo-700 bg-indigo-50' : 'text-gray-600 hover:bg-gray-100' }}">EOD reports</a>
            @endcan
        </div>
    </div>
@endif
@can('manage-employees')
    <a href="{{ tenant_route('tenant.staff.index') }}"
       class="{{ $navLink }} {{ request()->routeIs('tenant.staff.*') ? $navActive : $navIdle }}">
        <span>🧑‍💼</span> Staff
    </a>
@endcan
@can('manage-settings')
    <a href="{{ tenant_route('tenant.business.edit') }}"
       class="{{ $navLink }} {{ request()->routeIs('tenant.business.*') ? $navActive : $navIdle }}">
        <span>🏢</span> Business
    </a>
@endcan
@can('manage-profile')
    <a href="{{ tenant_route('tenant.profile.edit') }}"
       class="{{ $navLink }} {{ request()->routeIs('tenant.profile.*') ? $navActive : $navIdle }}">
        <span>⚙️</span> My profile
    </a>
@endcan
