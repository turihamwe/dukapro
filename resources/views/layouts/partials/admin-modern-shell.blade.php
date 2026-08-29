@php
    $user = auth()->user();
    $business = $user->business;
    $lowStockCount = 0;
    if ($business) {
        $lowStockCount = \App\Models\Product::query()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->get()
            ->filter(function ($product) {
                return $product->stock_quantity <= ($product->critical_threshold ?? \App\Support\AnalyticsDateRange::LOW_STOCK_THRESHOLD);
            })
            ->count();
    }
    $notificationCount = min(99, max($lowStockCount, 0));
    $supportPhone = \App\Models\SystemSetting::get('support_phone', '0755-825974');
    $supportEmail = \App\Models\SystemSetting::get('support_email', 'support@dukapro.net');
    $supportWebsite = \App\Models\SystemSetting::get('support_website', 'www.dukapro.net');
    $navActive = 'bg-emerald-500/15 text-emerald-400 border-l-[3px] border-emerald-500';
    $navIdle = 'text-slate-300 hover:bg-white/5 hover:text-white border-l-[3px] border-transparent';
@endphp

<div class="modern-app flex min-h-screen bg-[#F3F4F6]">
    {{-- Desktop sidebar --}}
    <aside class="modern-sidebar hidden w-64 shrink-0 flex-col p-4 lg:flex">
        <div class="flex flex-1 flex-col rounded-2xl bg-[#0A192F] px-3 py-5 shadow-xl">
            <div class="mb-8 flex items-center gap-3 px-3">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500 text-sm font-bold text-white shadow-lg shadow-emerald-500/30">D</div>
                <div>
                    <p class="text-sm font-bold text-white">DukaPro</p>
                    <p class="text-xs text-slate-400">{{ $business->name ?? 'Store' }}</p>
                </div>
            </div>

            <nav class="flex-1 space-y-1">
                @include('layouts.partials.modern-nav-links', compact('navActive', 'navIdle', 'lowStockCount'))
            </nav>

            <div class="mt-6 space-y-3 border-t border-white/10 px-3 pt-5">
                <div class="flex items-center gap-2 text-xs text-emerald-400">
                    <span class="inline-block h-2 w-2 rounded-full bg-emerald-400"></span>
                    Cloud-Based · Online
                </div>
                <a href="{{ route('logout.get') }}" class="flex items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-300 transition hover:bg-white/5 hover:text-white">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    Log out
                </a>
            </div>
        </div>
    </aside>

    {{-- Mobile drawer --}}
    <div id="modern-nav-backdrop" class="fixed inset-0 z-40 hidden bg-black/60 lg:hidden"></div>
    <aside id="modern-nav-drawer" class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full transform bg-[#0A192F] p-4 transition-transform duration-200 lg:hidden">
        <div class="mb-6 flex items-center justify-between">
            <p class="font-bold text-white">DukaPro</p>
            <button type="button" id="modern-nav-close" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-slate-400 hover:bg-white/10" aria-label="Close menu">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <nav class="space-y-1">
            @include('layouts.partials.modern-nav-links', compact('navActive', 'navIdle', 'lowStockCount'))
        </nav>
        <div class="absolute bottom-0 left-0 right-0 border-t border-white/10 p-4">
            <p class="mb-3 text-xs text-emerald-400">Cloud-Based · Online</p>
            <a href="{{ route('logout.get') }}" class="block rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-white/5">Log out</a>
        </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        @include('layouts.partials.trial-banner')

        {{-- Top header --}}
        <header class="sticky top-0 z-30 border-b border-gray-200/80 bg-white/95 px-4 py-3 backdrop-blur-sm sm:px-6 lg:px-8">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <button type="button" id="modern-nav-open" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-600 hover:bg-gray-100 lg:hidden" aria-label="Open menu">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                    </button>
                    <div class="flex items-center gap-2 lg:hidden">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-500 text-xs font-bold text-white">D</div>
                        <span class="font-bold text-gray-900">DukaPro</span>
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-4">
                    @can('switch-cashier-mode')
                        @if(!\App\Support\CashierMode::isActive())
                            <form method="POST" action="{{ tenant_route('tenant.cashier-mode.enable') }}">
                                @csrf
                                <button type="submit" class="inline-flex min-h-[44px] items-center rounded-lg bg-emerald-500 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-600">Cashier Mode</button>
                            </form>
                        @endif
                    @endcan

                    <button type="button" class="relative inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100" aria-label="Notifications">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                        @if($notificationCount > 0)
                            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">{{ $notificationCount > 9 ? '9+' : $notificationCount }}</span>
                        @endif
                    </button>

                    <a href="{{ tenant_route('tenant.profile.edit') }}" class="inline-flex min-h-[44px] min-w-[44px] items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100" aria-label="Settings">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </a>

                    <div class="flex items-center gap-2 border-l border-gray-200 pl-2 sm:pl-4">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-[#0A192F] text-xs font-bold text-white">
                            {{ strtoupper(substr($user->name, 0, 1)) }}{{ strtoupper(substr(strstr($user->name, ' ') ?: '', 1, 1)) }}
                        </div>
                        <div class="hidden min-w-0 sm:block">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs capitalize text-gray-500">{{ $user->role }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-auto px-4 py-6 sm:px-6 lg:px-8">
            @include('layouts.partials.flash')
            @yield('content')
        </main>

        {{-- Footer --}}
        <footer class="border-t border-gray-200 bg-white px-4 py-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-3 text-xs text-gray-500 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex flex-wrap gap-x-4 gap-y-1">
                    <span>Phone: {{ $supportPhone }}</span>
                    <span>Email: {{ $supportEmail }}</span>
                    <span>Website: {{ $supportWebsite }}</span>
                </div>
                <p class="text-gray-400">Trusted by 5,000+ Small Businesses · Secure · Cloud-Based · 24/7 Support</p>
            </div>
        </footer>
    </div>
</div>

@include('layouts.partials.payment-modal')

@push('scripts')
<script>
(function () {
    var drawer = document.getElementById('modern-nav-drawer');
    var backdrop = document.getElementById('modern-nav-backdrop');
    var openBtn = document.getElementById('modern-nav-open');
    var closeBtn = document.getElementById('modern-nav-close');

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
