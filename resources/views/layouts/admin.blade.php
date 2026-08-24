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
        <nav class="flex-1 space-y-1 p-4">
            @if(auth()->user()->can('view-dashboard'))
                <a href="{{ tenant_route('tenant.dashboard') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.dashboard') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span>📊</span> Dashboard
                </a>
            @endif
            @can('manage-inventory')
                <a href="{{ tenant_route('tenant.inventory.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.inventory.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span>📦</span> Inventory
                </a>
            @endcan
            @can('manage-debts')
                <a href="{{ tenant_route('tenant.debts.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.debts.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span>📒</span> Customer Debts
                </a>
            @endcan
            @can('log-damages')
                <a href="{{ tenant_route('tenant.damages.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.damages.*') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span>📉</span> Damages
                </a>
            @endcan
            @can('view-all-reconciliations')
                <a href="{{ tenant_route('tenant.reconciliation.index') }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition {{ request()->routeIs('tenant.reconciliation.index') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-gray-100' }}">
                    <span>💰</span> EOD Reports
                </a>
            @endcan
        </nav>
        <div class="border-t border-gray-200 p-4">
            <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
            <a href="{{ route('logout.get') }}" class="mt-2 inline-block text-xs text-gray-500 hover:text-gray-700">Sign out</a>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex h-14 items-center justify-between border-b border-gray-200 bg-white px-4 lg:hidden">
            <p class="font-semibold">{{ auth()->user()->business->name ?? 'DukaPro' }}</p>
            <a href="{{ route('logout.get') }}" class="text-sm text-gray-500">Sign out</a>
        </header>

        <main class="flex-1 overflow-auto p-4 sm:p-8">
            @include('layouts.partials.flash')
            @yield('content')
        </main>
    </div>
</div>
@endsection
