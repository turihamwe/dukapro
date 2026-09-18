@extends('layouts.landing')

@section('title', platform_brand('name') . ' - Stop using Paper Books to Manage Your Business - Switch to Digital')

@section('content')
@php
    $brand = platform_brand('name');
@endphp

{{-- Navigation --}}
<header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur-md">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <x-dukapro-logo size="header" href="{{ route('home') }}" class="shrink-0" />

        <nav class="hidden items-center gap-8 md:flex">
            <a href="#features" class="text-sm font-medium text-slate-600 transition hover:text-ink-900">Features</a>
            <a href="#pricing" class="text-sm font-medium text-slate-600 transition hover:text-ink-900">Pricing</a>
            <a href="#about" class="text-sm font-medium text-slate-600 transition hover:text-ink-900">About</a>
        </nav>

        <div class="hidden items-center gap-3 md:flex">
            <a href="{{ route('login') }}"
               class="rounded-lg px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 hover:text-ink-900">
                Login
            </a>
            <a href="{{ route('register') }}"
               class="rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                Start for FREE
            </a>
        </div>

        <button type="button"
                class="inline-flex items-center justify-center rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden"
                @click="mobileNav = !mobileNav"
                :aria-expanded="mobileNav.toString()"
                aria-label="Toggle menu">
            <svg x-show="!mobileNav" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
            <svg x-show="mobileNav" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    </div>

    <div x-show="mobileNav"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="border-t border-slate-200 bg-white px-4 py-4 md:hidden">
        <nav class="flex flex-col gap-1">
            <a href="#features" @click="mobileNav = false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Features</a>
            <a href="#pricing" @click="mobileNav = false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">Pricing</a>
            <a href="#about" @click="mobileNav = false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">About</a>
            <div class="mt-3 flex flex-col gap-2 border-t border-slate-100 pt-3">
                <a href="{{ route('login') }}" class="rounded-lg border border-slate-200 px-4 py-2.5 text-center text-sm font-semibold text-slate-700">Login</a>
                <a href="{{ route('register') }}" class="rounded-lg bg-brand-600 px-4 py-2.5 text-center text-sm font-semibold text-white">Start for FREE</a>
            </div>
        </nav>
    </div>
</header>

{{-- Hero --}}
<section class="relative overflow-hidden bg-ink-950 hero-grid">
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-brand-900/30 via-transparent to-indigo-950/40"></div>
    <div class="pointer-events-none absolute -right-32 top-20 h-96 w-96 rounded-full bg-brand-500/10 blur-3xl"></div>
    <div class="pointer-events-none absolute -left-24 bottom-0 h-72 w-72 rounded-full bg-indigo-500/10 blur-3xl"></div>

    <div class="relative mx-auto grid max-w-7xl gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-16 lg:px-8 lg:py-24">
        <div>
            <div class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-3 py-1 text-xs font-semibold uppercase tracking-wider text-emerald-300">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                Built for African businesses
            </div>
            <h1 class="text-4xl font-extrabold leading-[1.1] tracking-tight text-white sm:text-5xl lg:text-[3.25rem]">
                Stop Using Paper Books.<br>
                <span class="bg-gradient-to-r from-emerald-300 to-teal-200 bg-clip-text text-transparent">Manage Your Shop with <span class="text-white">{{ $brand }}.</span></span>
            </h1>
            <p class="mt-6 max-w-xl text-lg leading-relaxed text-slate-300">
                Replace manual black books, eliminate calculation errors, and take total control of sales, stock, and daily cash - from one professional platform your whole team can trust.
            </p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                <a href="{{ route('register') }}"
                   class="inline-flex items-center justify-center rounded-xl bg-brand-500 px-6 py-3.5 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600">
                    Get Started for FREE
                    <svg class="ml-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                    </svg>
                </a>
                <a href="{{ route('login') }}"
                   class="inline-flex items-center justify-center rounded-xl border border-white/15 bg-white/5 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10">
                    Sign in to your account
                </a>
            </div>
            <ul class="mt-10 grid gap-3 sm:grid-cols-3">
                <li class="flex items-center gap-2 text-sm text-slate-400">
                    <svg class="h-5 w-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    No setup headaches
                </li>
                <li class="flex items-center gap-2 text-sm text-slate-400">
                    <svg class="h-5 w-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Works on any device
                </li>
                <li class="flex items-center gap-2 text-sm text-slate-400">
                    <svg class="h-5 w-5 shrink-0 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Sales history preserved
                </li>
            </ul>
        </div>

        {{-- Dashboard mockup --}}
        <div class="relative lg:pl-4">
            <div class="animate-float rounded-2xl border border-white/10 bg-slate-900/80 p-1 shadow-lift backdrop-blur-sm">
                <div class="rounded-xl bg-slate-900 p-4 sm:p-5">
                    <div class="mb-4 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wider text-slate-500">Today's overview</p>
                            <p class="mt-0.5 text-lg font-bold text-white">Kampala General Store</p>
                        </div>
                        <span class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-semibold text-emerald-300">Live POS</span>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-lg border border-white/5 bg-white/5 p-3">
                            <p class="text-[10px] uppercase tracking-wide text-slate-500">Sales</p>
                            <p class="mt-1 text-sm font-bold text-white">UGX 2.4M</p>
                            <p class="mt-0.5 text-[10px] text-emerald-400">+12% vs yesterday</p>
                        </div>
                        <div class="rounded-lg border border-white/5 bg-white/5 p-3">
                            <p class="text-[10px] uppercase tracking-wide text-slate-500">Transactions</p>
                            <p class="mt-1 text-sm font-bold text-white">148</p>
                            <p class="mt-0.5 text-[10px] text-slate-400">Since opening</p>
                        </div>
                        <div class="rounded-lg border border-white/5 bg-white/5 p-3">
                            <p class="text-[10px] uppercase tracking-wide text-slate-500">Low stock</p>
                            <p class="mt-1 text-sm font-bold text-amber-300">6 items</p>
                            <p class="mt-0.5 text-[10px] text-slate-400">Needs attention</p>
                        </div>
                    </div>
                    <div class="mt-4 rounded-lg border border-white/5 bg-slate-950/60 p-3">
                        <div class="flex items-end justify-between gap-1 h-24 px-1">
                            @foreach([40, 65, 45, 80, 55, 90, 70] as $h)
                                <div class="flex-1 rounded-t bg-gradient-to-t from-brand-600 to-emerald-400 opacity-90" style="height: {{ $h }}%"></div>
                            @endforeach
                        </div>
                        <p class="mt-2 text-center text-[10px] text-slate-500">Weekly revenue trend</p>
                    </div>
                    <div class="mt-4 space-y-2">
                        @foreach([['Rice 25kg', 'UGX 45,000', '2×'], ['Cooking Oil 5L', 'UGX 28,000', '1×'], ['Sugar 2kg', 'UGX 8,500', '3×']] as [$name, $price, $qty])
                            <div class="flex items-center justify-between rounded-lg border border-white/5 bg-white/[0.03] px-3 py-2">
                                <div>
                                    <p class="text-xs font-medium text-white">{{ $name }}</p>
                                    <p class="text-[10px] text-slate-500">Recent sale</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-semibold text-emerald-300">{{ $price }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $qty }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="absolute -bottom-4 -left-4 hidden rounded-xl border border-emerald-500/20 bg-emerald-950/90 px-4 py-3 shadow-lift sm:block">
                <p class="text-xs font-semibold text-emerald-300">EOD report ready</p>
                <p class="text-[10px] text-emerald-100/70">Cash reconciled · Zero discrepancies</p>
            </div>
        </div>
    </div>
</section>

{{-- Trust strip --}}
<section class="border-b border-slate-200 bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <p class="text-center text-sm font-medium text-slate-500">{{ platform_footer_tagline() }}</p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-x-10 gap-y-4 text-sm font-semibold text-slate-400">
            <span>Retail &amp; wholesale</span>
            <span class="hidden h-1 w-1 rounded-full bg-slate-300 sm:inline-block"></span>
            <span>Restaurants &amp; bars</span>
            <span class="hidden h-1 w-1 rounded-full bg-slate-300 sm:inline-block"></span>
            <span>Pharmacies &amp; kiosks</span>
            <span class="hidden h-1 w-1 rounded-full bg-slate-300 sm:inline-block"></span>
            <span>Multi-branch operations</span>
        </div>
    </div>
</section>

{{-- Features --}}
<section id="features" class="py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">Platform capabilities</p>
            <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-ink-900 sm:text-4xl">
                Everything you need to run a modern shop in Africa
            </h2>
            <p class="mt-4 text-lg text-slate-600">
                From the cashier's counter to the dashboard, {{ $brand }} replaces scattered notebooks with one reliable system built for how African businesses actually operate.
            </p>
        </div>

        <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach([
                [
                    'title' => 'Real-Time POS',
                    'desc' => 'Ring up sales in seconds. Accept cash, mobile money, and invoice payments - with receipts your customers can trust.',
                    'icon' => 'M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z',
                    'icon_class' => 'bg-emerald-50 text-emerald-600 ring-emerald-100',
                ],
                [
                    'title' => 'Multi-Unit & Fractional Sales',
                    'desc' => 'Sell by piece, carton, kilogram, or fraction. No more manual conversions - stock updates automatically with every sale.',
                    'icon' => 'M4 7v10c0 2 1 3 3 3h10c2 0 3-1 3-3V7c0-2-1-3-3-3H7c-2 0-3 1-3 3zM9 11h6M12 8v6',
                    'icon_class' => 'bg-indigo-50 text-indigo-600 ring-indigo-100',
                ],
                [
                    'title' => 'Automated EOD Reports',
                    'desc' => 'Close the day with confidence. Cash reconciliation, executive summaries, and shortage tracking - without late-night math.',
                    'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 5H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                    'icon_class' => 'bg-violet-50 text-violet-600 ring-violet-100',
                ],
                [
                    'title' => 'Seamless Compliance',
                    'desc' => 'Stay audit-ready with structured sales records, EFRIS integration support, and complete transaction history you can export anytime.',
                    'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
                    'icon_class' => 'bg-sky-50 text-sky-600 ring-sky-100',
                ],
            ] as $feature)
                <article class="group rounded-2xl border border-slate-200 bg-white p-6 shadow-soft transition hover:-translate-y-1 hover:border-slate-300 hover:shadow-lift">
                    <div class="inline-flex h-12 w-12 items-center justify-center rounded-xl ring-1 {{ $feature['icon_class'] }}">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $feature['icon'] }}"/>
                        </svg>
                    </div>
                    <h3 class="mt-5 text-lg font-bold text-ink-900">{{ $feature['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $feature['desc'] }}</p>
                </article>
            @endforeach
        </div>
    </div>
</section>

{{-- Value proposition --}}
<section class="border-y border-slate-200 bg-slate-50 py-20 sm:py-24">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-2 lg:items-center">
            <div>
                <h2 class="text-3xl font-extrabold tracking-tight text-ink-900 sm:text-4xl">
                    Your black book can't tell you what you earned today. <span class="text-brand-600">We Can.</span>
                </h2>
                <p class="mt-4 text-lg text-slate-600">
                    Paper records hide shrinkage, slow down checkout, and make end-of-day reconciliation a guessing game. {{ $brand }} gives owners real numbers - live.
                </p>
                <ul class="mt-8 space-y-4">
                    @foreach([
                        'Eliminate manual calculation errors at the till',
                        'Know exact stock levels before you run out',
                        'Track staff sales and cash handovers transparently',
                        'Generate professional receipts and invoices instantly',
                    ] as $point)
                        <li class="flex items-start gap-3">
                            <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-700">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                            </span>
                            <span class="text-slate-700">{{ $point }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-soft">
                <blockquote class="text-lg font-medium leading-relaxed text-ink-900">
                    "We moved off paper in one afternoon. Sales are faster, stock is accurate, and I finally know my daily profit without staying up with my staff until midnight."
                </blockquote>
                <footer class="mt-6 flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-brand-100 text-sm font-bold text-brand-700">JM</div>
                    <div>
                        <p class="font-semibold text-ink-900">James M.</p>
                        <p class="text-sm text-slate-500">Retail owner, Kiyembe Lane, Kampala</p>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</section>

{{-- Pricing --}}
<section id="pricing" class="py-20 sm:py-28">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">Simple pricing</p>
            <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-ink-900 sm:text-4xl">
                Start free. Scale as you grow.
            </h2>
            <p class="mt-4 text-lg text-slate-600">
                No hidden fees. Choose the modules you need - pay only for what your business uses.
            </p>
        </div>

        <div class="mx-auto mt-14 grid max-w-4xl gap-6 md:grid-cols-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-soft">
                <p class="text-sm font-semibold uppercase tracking-wider text-slate-500">Starter</p>
                <p class="mt-4 text-4xl font-extrabold text-ink-900">Start for FREE</p>
                <p class="mt-2 text-slate-600">Start selling today with full access to POS and inventory during your trial period.</p>
                <ul class="mt-6 space-y-3 text-sm text-slate-700">
                    <li class="flex items-center gap-2"><svg class="h-4 w-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Point of sale &amp; receipts</li>
                    <li class="flex items-center gap-2"><svg class="h-4 w-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Inventory management</li>
                    <li class="flex items-center gap-2"><svg class="h-4 w-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Sales reports</li>
                </ul>
                <a href="{{ route('register') }}"
                   class="mt-8 block w-full rounded-xl bg-brand-600 py-3 text-center text-sm font-semibold text-white transition hover:bg-brand-700">
                    Start for FREE
                </a>
            </div>
            <div class="relative rounded-2xl border-2 border-brand-500 bg-white p-8 shadow-lift">
                <span class="absolute -top-3 left-6 rounded-full bg-brand-600 px-3 py-1 text-xs font-bold uppercase tracking-wide text-white">Popular</span>
                <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">Business</p>
                <p class="mt-4 text-4xl font-extrabold text-ink-900">Flexible plans</p>
                <p class="mt-2 text-slate-600">Add modules for restaurants, multi-branch, EFRIS, and advanced reporting.</p>
                <ul class="mt-6 space-y-3 text-sm text-slate-700">
                    <li class="flex items-center gap-2"><svg class="h-4 w-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Everything in Starter</li>
                    <li class="flex items-center gap-2"><svg class="h-4 w-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> EOD reconciliation &amp; shortages</li>
                    <li class="flex items-center gap-2"><svg class="h-4 w-4 text-brand-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Multi-staff &amp; role permissions</li>
                </ul>
                <a href="{{ route('register') }}"
                   class="mt-8 block w-full rounded-xl border-2 border-brand-600 py-3 text-center text-sm font-semibold text-brand-700 transition hover:bg-brand-50">
                    Contact sales
                </a>
            </div>
        </div>
    </div>
</section>

{{-- About --}}
<section id="about" class="border-t border-slate-200 bg-slate-50 py-20 sm:py-24">
    <div class="mx-auto max-w-3xl px-4 text-center sm:px-6 lg:px-8">
        <p class="text-sm font-semibold uppercase tracking-wider text-brand-600">About {{ $brand }}</p>
        <h2 class="mt-3 text-3xl font-extrabold tracking-tight text-ink-900 sm:text-4xl">
            Enterprise-grade tools, built for everyday shop owners in Africa
        </h2>
        <p class="mt-6 text-lg leading-relaxed text-slate-600">
            <span class="text-brand-600">{{ $brand }}</span> is retail and business management software designed for African markets - where connectivity varies, teams wear many hats, and owners need clarity without complexity. <span class="text-brand-600">We help you go digital</span> without losing control of your business.
        </p>
    </div>
</section>

{{-- CTA Banner --}}
<section class="py-16 sm:py-20">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-ink-900 via-ink-800 to-brand-900 px-6 py-14 text-center shadow-lift sm:px-12 sm:py-16">
            <div class="pointer-events-none absolute inset-0 hero-grid opacity-30"></div>
            <div class="relative">
                <h2 class="text-3xl font-extrabold tracking-tight text-white sm:text-4xl">
                    Ready to make the move to digital?
                </h2>
                <p class="mx-auto mt-4 max-w-2xl text-lg text-slate-300">
                    Join 5000+ business owners who <span class="text-orange-200">stopped using black books</span> and <span class="text-brand-600">started growing with real-time data</span> on their smartphones. Getting a free account takes minutes - not days.
                </p>
                <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                    <a href="{{ route('register') }}"
                       class="inline-flex w-full items-center justify-center rounded-xl bg-brand-600 px-8 py-3.5 text-sm font-bold text-white transition hover:bg-orange-400 sm:w-auto">
                        Get Free Account
                    </a>
                    <a href="{{ route('login') }}"
                       class="inline-flex w-full items-center justify-center rounded-xl border border-white/20 px-8 py-3.5 text-sm font-semibold text-white transition hover:bg-white/10 sm:w-auto">
                        Login to existing account
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Footer --}}
<footer class="border-t border-slate-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-10 md:grid-cols-4">
            <div class="md:col-span-2">
                <x-dukapro-logo size="md" />
                <p class="mt-4 max-w-sm text-sm leading-relaxed text-slate-600">
                    {{ platform_brand('tagline') }} <span class="text-brand-600">{{ $brand }}</span> is a modern POS, inventory, and business management for retailers who are done with paper books and are ready to <span class="text-brand-600">Switch to Digital</span>.
                </p>
            </div>
            <div>
                <p class="text-sm font-semibold text-ink-900">Product</p>
                <ul class="mt-4 space-y-2 text-sm text-slate-600">
                    <li><a href="#features" class="transition hover:text-brand-600">Features</a></li>
                    <li><a href="#pricing" class="transition hover:text-brand-600">Pricing</a></li>
                    <li><a href="{{ route('register') }}" class="transition hover:text-brand-600">Get Started for FREE</a></li>
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold text-ink-900">Account</p>
                <ul class="mt-4 space-y-2 text-sm text-slate-600">
                    <li><a href="{{ route('login') }}" class="transition hover:text-brand-600">Login</a></li>
                    <li><a href="{{ route('register') }}" class="transition hover:text-brand-600">Register</a></li>
                    <li><a href="{{ route('portal') }}" class="transition hover:text-brand-600">Business portal login</a></li>
                    @if(function_exists('whatsapp_support_url') && should_show_whatsapp_float())
                        <li>
                            <a href="{{ whatsapp_support_url('Hello, I need help with ' . $brand . '.') }}"
                               target="_blank"
                               rel="noopener noreferrer"
                               class="transition hover:text-brand-600">
                                WhatsApp support
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>
        <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-slate-200 pt-8 sm:flex-row">
            <p class="text-sm text-slate-500">&copy; {{ date('Y') }} {{ $brand }}. All rights reserved.</p>
            <p class="text-sm text-slate-400">Built for African businesses · Let's go digital</p>
        </div>
    </div>
</footer>

<style>[x-cloak] { display: none !important; }</style>
@endsection
