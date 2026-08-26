@extends('layouts.base')

@section('body')
<div class="flex min-h-full">
    <aside class="hidden w-64 shrink-0 border-r border-gray-200 bg-white lg:flex lg:flex-col">
        <div class="flex h-16 items-center gap-3 border-b border-gray-200 px-6">
            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-sm font-bold text-white">D</div>
            <div class="min-w-0">
                <p class="truncate text-sm font-semibold">{{ auth()->user()->business->name ?? 'DukaPro' }}</p>
                <p class="text-xs capitalize text-gray-500">{{ auth()->user()->role ?? 'Admin' }}</p>
            </div>
        </div>
        <nav class="flex-1 space-y-1 overflow-y-auto p-4">
            @can('view-dashboard')
                <a href="{{ tenant_route('tenant.dashboard') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span>📊</span> Dashboard
                </a>
            @endcan
            @can('view-inventory')
                <a href="{{ tenant_route('tenant.inventory.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.inventory.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span>📦</span> Inventory
                </a>
            @endcan
            @can('view-customers')
                <a href="{{ tenant_route('tenant.contacts.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.contacts.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span>👥</span> Contacts
                </a>
            @endcan
            @if(auth()->user()->can('view-sales-reports') || auth()->user()->can('view-all-reconciliations'))
                @php
                    $reportsOpen = request()->routeIs('tenant.reports.*') || request()->routeIs('tenant.reconciliation.index');
                @endphp
                <div class="reports-nav">
                    <button type="button" id="reports-toggle"
                            class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ $reportsOpen ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                        <span class="flex items-center gap-3"><span>📈</span> Reports</span>
                        <svg class="h-4 w-4 transition {{ $reportsOpen ? 'rotate-180' : '' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div id="reports-menu" class="ml-4 mt-1 space-y-1 {{ $reportsOpen ? '' : 'hidden' }}">
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
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.staff.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span>🧑‍💼</span> Staff
                </a>
            @endcan
            @can('manage-settings')
                <a href="{{ tenant_route('tenant.business.edit') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.business.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span>🏢</span> Business
                </a>
            @endcan
            @can('manage-profile')
                <a href="{{ tenant_route('tenant.profile.edit') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.profile.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span>⚙️</span> My profile
                </a>
            @endcan
        </nav>
        <div class="mt-auto border-t border-gray-200 p-4">
            <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
            <a href="{{ route('logout.get') }}" class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Logout
            </a>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        @include('layouts.partials.trial-banner')

        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white">
            <div class="flex h-14 items-center justify-between gap-4 px-4 lg:px-8">
                <div class="lg:hidden">
                    <p class="font-semibold">{{ auth()->user()->business->name ?? 'DukaPro' }}</p>
                </div>
                <div class="hidden text-sm text-gray-600 lg:block">
                    <span class="font-medium text-gray-900">{{ auth()->user()->business->name ?? 'DukaPro' }}</span>
                    <span class="mx-2 text-gray-300">·</span>
                    <span class="capitalize">{{ auth()->user()->role }}</span>
                </div>
                <div class="flex items-center gap-2">
                    @can('switch-cashier-mode')
                        @if(!\App\Support\CashierMode::isActive())
                            <form method="POST" action="{{ tenant_route('tenant.cashier-mode.enable') }}">
                                @csrf
                                <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">
                                    Switch into Cashier Mode
                                </button>
                            </form>
                        @endif
                    @endcan
                    <a href="{{ route('logout.get') }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 lg:hidden">Logout</a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-auto p-4 sm:p-8">
            @include('layouts.partials.flash')
            @yield('content')
        </main>
    </div>
</div>

@include('layouts.partials.payment-modal')

@push('scripts')
<script>
document.getElementById('reports-toggle')?.addEventListener('click', function () {
    document.getElementById('reports-menu')?.classList.toggle('hidden');
    this.querySelector('svg')?.classList.toggle('rotate-180');
});
</script>
@endpush
@endsection
