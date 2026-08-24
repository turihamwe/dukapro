@extends('layouts.base')

@section('body')
<div class="flex min-h-full flex-col bg-gray-100">
    <header class="sticky top-0 z-40 border-b border-gray-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-sm font-bold text-white">POS</div>
                <div>
                    <p class="text-sm font-semibold">{{ auth()->user()->business->name ?? 'DukaPro' }}</p>
                    <p class="text-xs text-gray-500">{{ auth()->user()->name }} · Cashier</p>
                </div>
            </div>
            <a href="{{ route('logout.get') }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700">Sign out</a>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 pb-24">
        @include('layouts.partials.flash')
        @yield('content')
    </main>

    <nav class="fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200 bg-white/95 backdrop-blur">
        <div class="mx-auto flex max-w-lg justify-around px-2 py-2">
            @can('access-pos')
                <a href="{{ tenant_route('tenant.pos.index') }}"
                   class="flex flex-col items-center rounded-lg px-4 py-1.5 text-[10px] font-medium {{ request()->routeIs('tenant.pos.*') ? 'text-emerald-600' : 'text-gray-500' }}">
                    <span class="mb-0.5 text-base">🛒</span> POS
                </a>
            @endcan
            @can('submit-reconciliation')
                <a href="{{ tenant_route('tenant.reconciliation.create') }}"
                   class="flex flex-col items-center rounded-lg px-4 py-1.5 text-[10px] font-medium {{ request()->routeIs('tenant.reconciliation.create') ? 'text-emerald-600' : 'text-gray-500' }}">
                    <span class="mb-0.5 text-base">💰</span> Close Shift
                </a>
                <a href="{{ tenant_route('tenant.reconciliation.index') }}"
                   class="flex flex-col items-center rounded-lg px-4 py-1.5 text-[10px] font-medium {{ request()->routeIs('tenant.reconciliation.index') ? 'text-emerald-600' : 'text-gray-500' }}">
                    <span class="mb-0.5 text-base">📋</span> My Shifts
                </a>
            @endcan
        </div>
    </nav>
</div>
@endsection
