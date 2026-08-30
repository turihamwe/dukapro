@extends('layouts.base')

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

<div class="cashier-shell flex min-h-[100dvh] flex-col bg-gray-100">
    @if(!auth()->user()->canSwitchToCashierMode() || !\App\Support\CashierMode::isActive())
        @include('layouts.partials.trial-banner')
    @endif

    <header class="sticky top-0 z-40 border-b border-gray-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
            <div class="flex min-w-0 items-center gap-3">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-600 text-sm font-bold text-white shadow-md">POS</div>
                <div class="min-w-0">
                    <p class="truncate text-sm font-bold text-gray-900">{{ auth()->user()->business->name ?? 'DukaPro' }}</p>
                    <p class="truncate text-xs text-gray-500">{{ auth()->user()->name }} · {{ ucfirst(auth()->user()->role) }}</p>
                </div>
            </div>
            @unless(auth()->user()->canSwitchToCashierMode() && \App\Support\CashierMode::isActive())
                <a href="{{ route('logout.get') }}" class="inline-flex min-h-[44px] items-center rounded-lg border border-gray-300 px-4 py-2 text-xs font-semibold text-gray-700">Logout</a>
            @endunless
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl flex-1 px-3 py-4 pb-28 sm:px-4 sm:py-6">
        @include('layouts.partials.flash')
        @yield('content')
    </main>

    <nav class="cashier-bottom-nav fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200 bg-white/95 backdrop-blur-md" style="padding-bottom: env(safe-area-inset-bottom);">
        <div class="mx-auto grid max-w-lg grid-cols-4 gap-1 px-2 py-2">
            @can('access-pos')
                <a href="{{ tenant_route('tenant.pos.index') }}"
                   class="flex min-h-[56px] flex-col items-center justify-center rounded-xl px-2 py-2 text-[11px] font-semibold {{ request()->routeIs('tenant.pos.*') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                    <span class="mb-0.5 text-xl leading-none">🛒</span> POS
                </a>
            @endcan
            @if(auth()->user()->usesCashierExperience())
                @can('view-inventory')
                    <a href="{{ tenant_route('tenant.inventory.index') }}"
                       class="flex min-h-[56px] flex-col items-center justify-center rounded-xl px-2 py-2 text-[11px] font-semibold {{ request()->routeIs('tenant.inventory.*') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                        <span class="mb-0.5 text-xl leading-none">📦</span> Stock
                    </a>
                @endcan
            @endif
            @can('record-expenses')
                <a href="{{ tenant_route('tenant.expenses.create') }}"
                   class="flex min-h-[56px] flex-col items-center justify-center rounded-xl px-2 py-2 text-[11px] font-semibold {{ request()->routeIs('tenant.expenses.create') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
                    <span class="mb-0.5 text-xl leading-none">📝</span> Expense
                </a>
            @endcan
            @can('submit-reconciliation')
                <a href="{{ tenant_route('tenant.reconciliation.create') }}"
                   class="flex min-h-[56px] flex-col items-center justify-center rounded-xl px-2 py-2 text-[11px] font-semibold {{ request()->routeIs('tenant.reconciliation.create') ? 'bg-emerald-50 text-emerald-700' : 'text-gray-600' }}">
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
</style>
@endpush
@endsection
