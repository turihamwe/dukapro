@php
    $variant = $variant ?? 'header';
@endphp

@can('switch-cashier-mode')
    @if(! \App\Support\CashierMode::isActive() && ! show_subscription_expired_overlay())
        <form method="POST"
              action="{{ tenant_route('tenant.cashier-mode.enable') }}"
              class="{{ $variant === 'drawer' ? 'w-full' : 'inline' }}">
            @csrf
            @if($variant === 'drawer')
                <button type="submit"
                        class="flex w-full min-h-[44px] items-center justify-center gap-2 rounded-lg bg-emerald-600 px-3 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700">
                    <span aria-hidden="true">🛒</span>
                    Switch to Cashier Mode
                </button>
            @else
                <button type="submit"
                        class="inline-flex min-h-[44px] items-center justify-center rounded-lg bg-emerald-600 px-2.5 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 sm:px-3">
                    <span class="sm:hidden">Cashier</span>
                    <span class="hidden sm:inline">Switch to Cashier Mode</span>
                </button>
            @endif
        </form>
    @elseif(\App\Support\CashierMode::isActive() && $variant === 'drawer')
        <form method="POST"
              action="{{ tenant_route('tenant.cashier-mode.disable') }}"
              class="mb-2 w-full">
            @csrf
            <button type="submit"
                    class="flex w-full min-h-[44px] items-center justify-center rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2.5 text-sm font-semibold text-indigo-800 hover:bg-indigo-100">
                Exit Cashier Mode
            </button>
        </form>
    @endif
@endcan
