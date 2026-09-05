@extends('layouts.base')

@push('body-class')
has-cashier-bottom-nav
@endpush

@section('body')
@include('layouts.partials.impersonation-banner')

@php
    $isWaiter = auth()->user()->isWaiter();
    $isChef = auth()->user()->isChef();
    $staffNavCols = 0;
    if (auth()->user()->can('access-waiter-orders')) {
        $staffNavCols++;
    }
    if ($isChef && auth()->user()->can('access-kitchen')) {
        $staffNavCols++;
    }
    if (auth()->user()->can('settle-kitchen-orders')) {
        $staffNavCols++;
    }
    $staffNavCols = max($staffNavCols, 1);
@endphp

<div class="cashier-shell flex min-h-[100dvh] flex-col bg-gray-100">
    @include('layouts.partials.trial-banner')

    <header class="sticky top-0 z-40 border-b border-gray-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
            <div class="flex min-w-0 items-center gap-3">
                <x-dukapro-logo size="cashier" />
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-gray-900">{{ auth()->user()->business->name ?? platform_brand('name') }}</p>
                    <p class="truncate text-xs text-gray-500">{{ auth()->user()->name }} · {{ \App\Enums\UserRole::label(auth()->user()->role) }}</p>
                </div>
            </div>
            <a href="{{ route('logout.get') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700">Logout</a>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-3 py-4 pb-28 sm:px-4 sm:py-6 @yield('main_class')">
        @include('layouts.partials.flash')
        @yield('content')
    </main>

    <nav class="cashier-bottom-nav fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200 bg-white/95 backdrop-blur-md" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="mx-auto grid max-w-lg gap-1 px-2 py-2" style="grid-template-columns: repeat({{ $staffNavCols }}, minmax(0, 1fr));">
            @can('access-waiter-orders')
                <a href="{{ tenant_route('tenant.waiter-orders.index') }}"
                   class="flex min-h-[56px] flex-col items-center justify-center rounded-xl px-2 py-2 text-[11px] font-semibold {{ request()->routeIs('tenant.waiter-orders.*') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                    <span class="mb-0.5 text-xl leading-none">🍽</span> New Order
                </a>
            @endcan
            @if($isChef)
                @can('access-kitchen')
                    <a href="{{ tenant_route('tenant.kitchen.index') }}"
                       class="flex min-h-[56px] flex-col items-center justify-center rounded-xl px-2 py-2 text-[11px] font-semibold {{ request()->routeIs('tenant.kitchen.index') ? 'bg-orange-50 text-orange-700' : 'text-gray-600' }}">
                        <span class="mb-0.5 text-xl leading-none">👨‍🍳</span> Kitchen
                    </a>
                @endcan
            @endif
            @can('settle-kitchen-orders')
                <a href="{{ tenant_route('tenant.kitchen.ready') }}"
                   class="flex min-h-[56px] flex-col items-center justify-center rounded-xl px-2 py-2 text-[11px] font-semibold {{ request()->routeIs('tenant.kitchen.ready') || request()->routeIs('tenant.kitchen.settle*') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                    <span class="mb-0.5 text-xl leading-none">🍳</span> Ready
                </a>
            @endcan
        </div>
    </nav>
</div>
@endsection
