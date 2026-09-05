@extends('layouts.base')

@push('body-class')
has-cashier-bottom-nav
@endpush

@section('body')
@include('layouts.partials.impersonation-banner')
@if(auth()->user()->canSwitchToCashierMode() && \App\Support\CashierMode::isActive())
    <div class="sticky top-0 z-[60] border-b border-indigo-200 bg-indigo-50 px-4 py-2 text-center text-xs text-indigo-800">
        <span>Cashier Mode active</span>
        <span class="mx-2 text-indigo-300">·</span>
        <form method="POST" action="{{ tenant_route('tenant.cashier-mode.disable') }}" class="inline">
            @csrf
            <button type="submit" class="font-medium text-indigo-700 underline decoration-indigo-300/70 underline-offset-2 hover:text-indigo-900">
                Exit cashier mode
            </button>
        </form>
    </div>
@endif

@php
    $showOperationsNav = auth()->user()->can('view-inventory')
        || auth()->user()->can('record-expenses')
        || auth()->user()->can('log-damages');
    $operationsActive = request()->routeIs('tenant.operations.*')
        || request()->routeIs('tenant.inventory.*')
        || request()->routeIs('tenant.expenses.*')
        || request()->routeIs('tenant.damages.*');
    $navCols = 0;
    if (auth()->user()->can('access-pos')) {
        $navCols++;
    }
    if (auth()->user()->can('access-waiter-shift-balancing')) {
        $navCols++;
    }
    if (auth()->user()->can('view-restaurant-orders')) {
        $navCols++;
    }
    if (auth()->user()->can('settle-kitchen-orders')) {
        $navCols++;
    }
    if ($showOperationsNav) {
        $navCols++;
    }
    if (auth()->user()->can('submit-reconciliation')) {
        $navCols++;
    }
    $navCols = max($navCols, 1);
@endphp

<div class="cashier-shell flex min-h-[100dvh] flex-col bg-gray-100 @yield('cashier_shell_class')">
    @if(!auth()->user()->canSwitchToCashierMode() || !\App\Support\CashierMode::isActive())
        @include('layouts.partials.trial-banner')
    @endif

    <header class="sticky top-0 z-40 border-b border-gray-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
            <div class="flex min-w-0 items-center gap-3">
                <x-dukapro-logo size="cashier" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-gray-900">{{ auth()->user()->business->name ?? platform_brand('name') }}</p>
                    <p class="truncate text-xs text-gray-500">{{ auth()->user()->name }} · {{ ucfirst(auth()->user()->role) }}</p>
                </div>
            </div>
            @unless(auth()->user()->canSwitchToCashierMode() && \App\Support\CashierMode::isActive())
                <a href="{{ route('logout.get') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700">Logout</a>
            @endunless
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-3 py-4 pb-28 sm:px-4 sm:py-6 @yield('main_class')">
        @include('layouts.partials.flash')
        @yield('content')
    </main>

    <nav class="cashier-bottom-nav fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200 bg-white/95 backdrop-blur-md" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="mx-auto grid max-w-lg gap-1 px-2 py-2" style="grid-template-columns: repeat({{ $navCols }}, minmax(0, 1fr));">
            @can('access-pos')
                <a href="{{ tenant_route('tenant.pos.index') }}"
                   class="flex min-h-[56px] flex-col items-center justify-center rounded-xl px-2 py-2 text-[11px] font-semibold {{ request()->routeIs('tenant.pos.*') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                    <span class="mb-0.5 text-xl leading-none">🛒</span> POS
                </a>
            @endcan
            @can('access-waiter-shift-balancing')
                <a href="{{ tenant_route('tenant.waiter-shift.index') }}"
                   class="flex min-h-[56px] flex-col items-center justify-center rounded-xl px-2 py-2 text-[11px] font-semibold {{ request()->routeIs('tenant.waiter-shift.*') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                    <span class="mb-0.5 text-xl leading-none">🍽</span> Waiters
                </a>
            @endcan
            @can('view-restaurant-orders')
                <a href="{{ tenant_route('tenant.restaurant-orders.index') }}"
                   class="flex min-h-[56px] flex-col items-center justify-center rounded-xl px-2 py-2 text-[11px] font-semibold {{ request()->routeIs('tenant.restaurant-orders.*') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                    <span class="mb-0.5 text-xl leading-none">📋</span> Orders
                </a>
            @endcan
            @can('settle-kitchen-orders')
                <a href="{{ tenant_route('tenant.kitchen.ready') }}"
                   class="flex min-h-[56px] flex-col items-center justify-center rounded-xl px-2 py-2 text-[11px] font-semibold {{ request()->routeIs('tenant.kitchen.ready') || request()->routeIs('tenant.kitchen.settle*') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                    <span class="mb-0.5 text-xl leading-none">🍳</span> Ready
                </a>
            @endcan
            @if($showOperationsNav)
                <a href="{{ tenant_route('tenant.operations.index') }}"
                   class="flex min-h-[56px] flex-col items-center justify-center rounded-xl px-2 py-2 text-[11px] font-semibold {{ $operationsActive ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                    <span class="mb-0.5 text-xl leading-none">⚙</span> Operations
                </a>
            @endif
            @can('submit-reconciliation')
                <a href="{{ tenant_route('tenant.reconciliation.create') }}"
                   class="flex min-h-[56px] flex-col items-center justify-center rounded-xl px-2 py-2 text-[11px] font-semibold {{ request()->routeIs('tenant.reconciliation.*') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                    <span class="mb-0.5 text-xl leading-none">💰</span> Close Shift
                </a>
            @endcan
        </div>
    </nav>
</div>

@push('styles')
<style>
    .cashier-shell { min-height: 100dvh; }
    .cashier-bottom-nav a { touch-action: manipulation; }

    @media (min-width: 1024px) {
        .cashier-shell--operations-hub .cashier-main,
        .cashier-shell--operations-hub main {
            display: flex;
            flex-direction: column;
            justify-content: center;
            max-height: calc(100dvh - 7rem);
        }
    }
</style>
@endpush
@endsection
