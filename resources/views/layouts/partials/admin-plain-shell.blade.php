@php
    $navLink = 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition';
    $navActive = 'bg-indigo-50 text-indigo-700';
    $navIdle = 'text-gray-700 hover:bg-gray-100';
@endphp
<div class="flex min-h-full theme-plain">
    {{-- Desktop sidebar --}}
    <aside class="theme-sidebar hidden w-64 shrink-0 border-r border-gray-200 bg-white lg:flex lg:flex-col">
        <div class="flex h-16 items-center gap-3 border-b border-gray-200 px-6">
            @include('layouts.partials.dukapro-sidebar-brand', [
                'subtitle' => (auth()->user()->business->name ?? 'Store') . ' · ' . ucfirst(auth()->user()->role ?? 'staff'),
            ])
        </div>
        <nav class="flex-1 space-y-1 overflow-y-auto p-4">
            @include('layouts.partials.admin-nav-links', ['navLink' => $navLink, 'navActive' => $navActive, 'navIdle' => $navIdle])
        </nav>
        <div class="mt-auto border-t border-gray-200 p-4">
            <p class="truncate text-xs text-gray-500">{{ auth()->user()->email }}</p>
            <a href="{{ route('logout.get') }}" class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50">
                Logout
            </a>
        </div>
    </aside>

    {{-- Mobile drawer --}}
    <div id="mobile-nav-backdrop" class="fixed inset-0 z-40 hidden bg-gray-900/50 lg:hidden" aria-hidden="true"></div>
    <aside id="mobile-nav-drawer" class="fixed inset-y-0 left-0 z-50 w-72 max-w-[85vw] -translate-x-full transform border-r border-gray-200 bg-white transition-transform duration-200 ease-out lg:hidden">
        <div class="flex h-14 items-center justify-between gap-3 border-b border-gray-200 px-4">
            <div class="min-w-0 flex-1">
                <x-dukapro-logo size="header" />
                <p class="truncate text-xs text-gray-500">{{ auth()->user()->business->name ?? '' }}</p>
            </div>
            <button type="button" id="mobile-nav-close" class="rounded-lg p-2 text-gray-500 hover:bg-gray-100" aria-label="Close menu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <nav class="space-y-1 overflow-y-auto p-4 pb-24" style="max-height: calc(100vh - 3.5rem);">
            @include('layouts.partials.admin-nav-links', ['navLink' => $navLink, 'navActive' => $navActive, 'navIdle' => $navIdle, 'mobile' => true])
        </nav>
        <div class="absolute bottom-0 left-0 right-0 border-t border-gray-200 bg-white p-4">
            <a href="{{ route('logout.get') }}" class="flex w-full items-center justify-center rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700">Logout</a>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        @include('layouts.partials.trial-banner')
        @include('layouts.partials.impersonation-banner')

        <header class="sticky top-0 z-30 border-b border-gray-200 bg-white">
            <div class="flex h-14 items-center justify-between gap-4 px-4 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" id="mobile-nav-open" class="rounded-lg p-2 text-gray-600 hover:bg-gray-100 lg:hidden" aria-label="Open menu">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="min-w-0 lg:hidden">
                        <x-dukapro-logo size="header" />
                    </div>
                </div>
                <div class="hidden text-sm text-gray-600 lg:block">
                    <span class="font-medium text-gray-900">{{ auth()->user()->business->name ?? 'DukaPro' }}</span>
                    <span class="mx-2 text-gray-300">·</span>
                    <span class="capitalize">{{ auth()->user()->role }}</span>
                </div>
                <div class="flex items-center gap-2">
                    @can('switch-cashier-mode')
                        @if(!\App\Support\CashierMode::isActive() && !show_subscription_expired_overlay())
                            <form method="POST" action="{{ tenant_route('tenant.cashier-mode.enable') }}">
                                @csrf
                                <button type="submit" class="hidden rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700 sm:inline-flex">
                                    Switch to Cashier Mode
                                </button>
                            </form>
                        @endif
                    @endcan
                    <a href="{{ route('logout.get') }}" class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 lg:hidden">Logout</a>
                </div>
            </div>
        </header>

        <main class="theme-main relative flex-1 overflow-auto p-4 sm:p-8">
            @include('layouts.partials.flash')
            @yield('content')
            @include('layouts.partials.subscription-expired-overlay')
        </main>
    </div>
</div>

@include('layouts.partials.payment-modal')

@push('scripts')
<script>
(function () {
    var drawer = document.getElementById('mobile-nav-drawer');
    var backdrop = document.getElementById('mobile-nav-backdrop');
    var openBtn = document.getElementById('mobile-nav-open');
    var closeBtn = document.getElementById('mobile-nav-close');

    function openNav() {
        if (!drawer || !backdrop) return;
        backdrop.classList.remove('hidden');
        drawer.classList.remove('-translate-x-full');
        document.body.classList.add('overflow-hidden');
    }

    function closeNav() {
        if (!drawer || !backdrop) return;
        backdrop.classList.add('hidden');
        drawer.classList.add('-translate-x-full');
        document.body.classList.remove('overflow-hidden');
    }

    openBtn?.addEventListener('click', openNav);
    closeBtn?.addEventListener('click', closeNav);
    backdrop?.addEventListener('click', closeNav);
    drawer?.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', closeNav);
    });
})();
</script>
@endpush
