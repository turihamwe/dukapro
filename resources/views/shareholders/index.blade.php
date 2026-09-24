@extends('layouts.landing')

@section('title', 'Shareholder Program — Co-Own ' . $brand . ' | ' . $brand)

@push('styles')
<style>
    [x-cloak] { display: none !important; }
    .investor-grid {
        background-image:
            linear-gradient(to right, rgba(148, 163, 184, 0.06) 1px, transparent 1px),
            linear-gradient(to bottom, rgba(148, 163, 184, 0.06) 1px, transparent 1px);
        background-size: 40px 40px;
    }
</style>
@endpush

@section('content')
@php
    $poolFromExample = (int) round($exampleSubscription * ($poolPercent / 100));
    $allocatedPercent = $totalShares > 0 ? min(100, round(($allocatedShares / $totalShares) * 100, 1)) : 0;
    $investUrl = $subscriptionOpen ? '#apply' : whatsapp_support_url('Hi, I am interested in the DukaPro shareholder program.');
@endphp

{{-- Nav --}}
<header class="sticky top-0 z-50 border-b border-slate-200/80 bg-white/90 backdrop-blur-md">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <x-dukapro-logo size="header" class="shrink-0" />
        <nav class="hidden items-center gap-8 md:flex">
            <a href="#opportunity" class="text-sm font-medium text-slate-600 transition hover:text-ink-900">Opportunity</a>
            <a href="#calculator" class="text-sm font-medium text-slate-600 transition hover:text-ink-900">Projections</a>
            <a href="#how-it-works" class="text-sm font-medium text-slate-600 transition hover:text-ink-900">How it works</a>
            <a href="#faq" class="text-sm font-medium text-slate-600 transition hover:text-ink-900">FAQ</a>
        </nav>
        <div class="hidden items-center gap-3 md:flex">
            <a href="{{ route('shareholder.login') }}" class="text-sm font-semibold text-slate-700 hover:text-ink-900">Shareholder login</a>
            <a href="{{ $investUrl }}"
               class="rounded-lg bg-ink-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-ink-800">
                Claim your share
            </a>
        </div>
        <button type="button" class="rounded-lg p-2 text-slate-600 hover:bg-slate-100 md:hidden" @click="mobileNav = !mobileNav" aria-label="Menu">
            <svg x-show="!mobileNav" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/></svg>
            <svg x-show="mobileNav" x-cloak class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>
    <div x-show="mobileNav" x-cloak class="border-t border-slate-200 bg-white px-4 py-4 md:hidden">
        <nav class="flex flex-col gap-1">
            <a href="#opportunity" @click="mobileNav = false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700">Opportunity</a>
            <a href="#calculator" @click="mobileNav = false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700">Projections</a>
            <a href="#how-it-works" @click="mobileNav = false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700">How it works</a>
            <a href="#faq" @click="mobileNav = false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-slate-700">FAQ</a>
            <a href="{{ $investUrl }}" class="mt-2 rounded-lg bg-ink-900 px-4 py-2.5 text-center text-sm font-semibold text-white">Claim your share</a>
        </nav>
    </div>
</header>

{{-- Hero --}}
<section class="relative overflow-hidden bg-ink-950 investor-grid">
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-violet-900/25 via-transparent to-brand-900/20"></div>
    <div class="pointer-events-none absolute -right-40 top-10 h-[28rem] w-[28rem] rounded-full bg-violet-500/10 blur-3xl"></div>
    <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
        <div class="mx-auto max-w-4xl text-center">
            <p class="mb-6 inline-flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-violet-200">
                <span class="h-2 w-2 animate-pulse rounded-full bg-emerald-400"></span>
                {{ number_format($remainingShares, 0) }} shares remaining · UGX {{ number_format($pricePerShare, 0) }} each
            </p>
            <h1 class="text-4xl font-extrabold tracking-tight text-white sm:text-5xl lg:text-6xl lg:leading-[1.08]">
                Own a Piece of Africa's Retail Revolution.
            </h1>
            <p class="mx-auto mt-6 max-w-2xl text-lg leading-relaxed text-slate-300 sm:text-xl">
                A rare opportunity to co-own <strong class="font-semibold text-white">{{ $brand }}</strong> and earn passive monthly revenue from every active merchant on the platform — backed by live production software and real shops across Uganda and East Africa.
            </p>
            <div class="mt-10 flex flex-col items-center justify-center gap-3 sm:flex-row">
                <a href="{{ $investUrl }}"
                   class="inline-flex w-full items-center justify-center rounded-xl bg-white px-8 py-4 text-sm font-bold text-ink-900 shadow-lift transition hover:bg-slate-100 sm:w-auto">
                    Claim Your Share
                </a>
                <a href="#calculator"
                   class="inline-flex w-full items-center justify-center rounded-xl border border-white/20 bg-white/5 px-8 py-4 text-sm font-semibold text-white transition hover:bg-white/10 sm:w-auto">
                    View Financial Projections
                </a>
            </div>
            <dl class="mt-14 grid gap-6 border-t border-white/10 pt-10 sm:grid-cols-3">
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Share pool</dt>
                    <dd class="mt-1 text-2xl font-bold text-white">{{ number_format($totalShares, 0) }} shares</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Live businesses</dt>
                    <dd class="mt-1 text-2xl font-bold text-emerald-300">{{ number_format($liveBusinesses) }}+</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active subscribers</dt>
                    <dd class="mt-1 text-2xl font-bold text-violet-200">{{ number_format($activeSubscribers) }}</dd>
                </div>
            </dl>
        </div>
    </div>
</section>

{{-- Scarcity --}}
<section class="border-b border-slate-200 bg-white py-10" id="availability">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-6 sm:p-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="text-lg font-bold text-ink-900">Limited allocation — {{ number_format($maxShareholders) }} shareholders maximum</h2>
                    <p class="mt-2 max-w-xl text-sm text-slate-600">
                        Only <strong>{{ number_format($totalShares, 0) }} shares</strong> exist. Each investor may hold one or more shares until the pool is fully subscribed.
                        <strong>{{ number_format($shareholderCount) }}</strong> shareholder{{ $shareholderCount === 1 ? '' : 's' }} onboarded so far.
                    </p>
                </div>
                <div class="w-full lg:max-w-md">
                    <div class="mb-2 flex justify-between text-xs font-semibold uppercase tracking-wide text-slate-500">
                        <span>Allocated</span>
                        <span>{{ number_format($allocatedShares, 1) }} / {{ number_format($totalShares, 0) }}</span>
                    </div>
                    <div class="h-3 overflow-hidden rounded-full bg-slate-200">
                        <div class="h-full rounded-full bg-gradient-to-r from-violet-600 to-brand-500 transition-all duration-700"
                             style="width: {{ $allocatedPercent }}%"></div>
                    </div>
                    <p class="mt-2 text-right text-sm font-bold text-violet-700">{{ number_format($remainingShares, 2) }} shares left</p>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Opportunity --}}
<section class="py-20 lg:py-24" id="opportunity">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <p class="text-sm font-semibold uppercase tracking-widest text-brand-600">Why {{ $brand }}</p>
            <h2 class="mt-3 text-3xl font-bold tracking-tight text-ink-900 sm:text-4xl">Production-ready platform. Massive retail whitespace.</h2>
            <p class="mt-4 text-lg text-slate-600">
                Millions of shops still run on paper ledgers. {{ $brand }} is live in production — POS, inventory, subscriptions, and mobile money — built for African merchants.
            </p>
        </div>
        <div class="mt-14 grid gap-6 md:grid-cols-3">
            <article class="rounded-2xl border border-slate-200 bg-white p-8 shadow-soft">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15m-7.5 0v18"/></svg>
                </div>
                <h3 class="mt-5 text-lg font-bold text-ink-900">Live &amp; shipping daily</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">Not a pitch deck — real tenants, real sales, and recurring subscription revenue on infrastructure we operate today.</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-8 shadow-soft">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-violet-50 text-violet-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72"/></svg>
                </div>
                <h3 class="mt-5 text-lg font-bold text-ink-900">East Africa scale</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">Uganda first, with a repeatable GTM across the region as digitized retail accelerates.</p>
            </article>
            <article class="rounded-2xl border border-slate-200 bg-white p-8 shadow-soft">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.004 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="mt-5 text-lg font-bold text-ink-900">Aligned revenue share</h3>
                <p class="mt-2 text-sm leading-relaxed text-slate-600">{{ $poolPercent }}% of every active business subscription flows to shareholders — transparent math, monthly pool distributions.</p>
            </article>
        </div>
    </div>
</section>

{{-- Transparent math + calculator --}}
<section class="border-y border-slate-200 bg-slate-50 py-20 lg:py-24" id="calculator"
         x-data="shareholderCalculator({
            pricePerShare: {{ (int) $pricePerShare }},
            perBusinessPerShare: {{ (int) $ugxPerBusinessPerShare }},
            capMultiplier: {{ (int) $capMultiplier }},
            maxShares: {{ (int) $calculatorMaxShares }},
            scenarios: [1000, 5000, 10000]
         })">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-12 lg:grid-cols-2 lg:gap-16">
            <div>
                <p class="text-sm font-semibold uppercase tracking-widest text-violet-600">Transparent economics</p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight text-ink-900">How the pool pays you</h2>
                <ul class="mt-8 space-y-5 text-sm text-slate-700">
                    <li class="flex gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ink-900 text-xs font-bold text-white">1</span>
                        <span><strong>{{ $poolPercent }}% of monthly subscription</strong> from each active {{ $brand }} business is allocated to the shareholder pool.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ink-900 text-xs font-bold text-white">2</span>
                        <span>Example: UGX {{ number_format($exampleSubscription) }} subscription → <strong>UGX {{ number_format($poolFromExample) }}</strong> to the pool that month.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ink-900 text-xs font-bold text-white">3</span>
                        <span>Pool split across {{ number_format($totalShares, 0) }} shares = <strong>UGX {{ number_format($ugxPerBusinessPerShare) }}</strong> per active business, per share, per month.</span>
                    </li>
                    <li class="flex gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ink-900 text-xs font-bold text-white">4</span>
                        <span>At <strong>10,000</strong> active subscribers, one share targets <strong>UGX 1,000,000 / month</strong>. Contracts conclude after a <strong>{{ $capMultiplier }}×</strong> return cap (UGX {{ number_format($pricePerShare * $capMultiplier) }} per share).</span>
                    </li>
                </ul>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-soft sm:p-8">
                <h3 class="text-lg font-bold text-ink-900">Interactive projection calculator</h3>
                <p class="mt-1 text-sm text-slate-500">Model your investment before you apply.</p>

                <label class="mt-6 block text-sm font-semibold text-slate-700">Number of shares</label>
                <div class="mt-2 flex items-center gap-4">
                    <input type="range" min="1" :max="maxShares" x-model.number="shares" class="w-full accent-violet-600">
                    <input type="number" min="1" :max="maxShares" x-model.number="shares"
                           class="w-20 rounded-lg border border-slate-300 px-2 py-1.5 text-center text-sm font-bold">
                </div>
                <p class="mt-2 text-xs text-slate-500">Capital required: <span class="font-semibold text-ink-900" x-text="formatUgx(investmentTotal())"></span></p>

                <div class="mt-8 overflow-hidden rounded-xl border border-slate-100">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Active businesses</th>
                                <th class="px-4 py-3 text-right">Est. monthly payout</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <template x-for="businesses in scenarios" :key="businesses">
                                <tr>
                                    <td class="px-4 py-3 font-medium text-slate-800" x-text="formatNumber(businesses)"></td>
                                    <td class="px-4 py-3 text-right font-bold text-brand-700" x-text="formatUgx(monthlyAt(businesses))"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 rounded-xl bg-violet-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-violet-800">{{ $capMultiplier }}× lifetime return cap (per share)</p>
                    <p class="mt-1 text-2xl font-bold text-violet-900" x-text="formatUgx(capTotal())"></p>
                    <p class="mt-1 text-xs text-violet-700">Total distributions across your selected shares until the contract completes.</p>
                </div>

                <a href="{{ $investUrl }}" class="mt-6 inline-flex w-full items-center justify-center rounded-xl bg-ink-900 py-3.5 text-sm font-semibold text-white hover:bg-ink-800">
                    Reserve shares at this allocation
                </a>
            </div>
        </div>
    </div>
</section>

{{-- How it works --}}
<section class="py-20 lg:py-24" id="how-it-works">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold tracking-tight text-ink-900">How it works</h2>
            <p class="mt-3 text-slate-600">Three steps from interest to monthly pool dividends.</p>
        </div>
        <ol class="mt-14 grid gap-8 md:grid-cols-3">
            @foreach([
                ['step' => '01', 'title' => 'Select your share(s)', 'body' => 'Choose how many of the 100 shares you want to acquire at UGX ' . number_format($pricePerShare, 0) . ' each — single or multiple.'],
                ['step' => '02', 'title' => 'Complete secure payment', 'body' => 'Finish onboarding and pay via approved channels (including mobile money) through your shareholder dashboard.'],
                ['step' => '03', 'title' => 'Track monthly dividends', 'body' => 'Monitor pool earnings, payout history, and contract progress toward your ' . $capMultiplier . '× return cap in the shareholder portal.'],
            ] as $item)
                <li class="relative rounded-2xl border border-slate-200 bg-white p-8 pt-12 shadow-soft">
                    <span class="absolute left-8 top-0 -translate-y-1/2 rounded-lg bg-ink-900 px-3 py-1 text-xs font-bold text-white">{{ $item['step'] }}</span>
                    <h3 class="text-lg font-bold text-ink-900">{{ $item['title'] }}</h3>
                    <p class="mt-3 text-sm leading-relaxed text-slate-600">{{ $item['body'] }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</section>

{{-- FAQ --}}
<section class="border-t border-slate-200 bg-slate-50 py-20" id="faq" x-data="{ open: 1 }">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <h2 class="text-center text-3xl font-bold text-ink-900">Frequently asked questions</h2>
        <div class="mt-10 space-y-3">
            @foreach([
                ['q' => 'How do I pay for my shares?', 'a' => 'After you submit your application, you receive access to the shareholder dashboard where payment instructions and mobile money checkout (YoPayments) are provided. Funds must clear before shares are fully allocated.'],
                ['q' => 'Can I buy more than one share?', 'a' => 'Yes. You may purchase multiple shares in a single application subject to remaining pool availability (maximum ' . number_format($totalShares, 0) . ' shares across all investors).'],
                ['q' => 'When are dividends paid?', 'a' => 'Pool distributions are calculated from active subscription revenue each month and recorded to your shareholder account. Payout timing and withdrawal options are shown on your dashboard.'],
                ['q' => 'What is the 10× return cap?', 'a' => 'Each share targets up to ' . $capMultiplier . '× your invested capital (UGX ' . number_format($pricePerShare * $capMultiplier, 0) . ' per UGX ' . number_format($pricePerShare, 0) . ' share) in cumulative distributions. When that cap is reached, the shareholder contract concludes.'],
                ['q' => 'Are returns guaranteed?', 'a' => 'No. Projections depend on merchant growth and subscription retention. The calculator shows illustrative scenarios at 1,000, 5,000, and 10,000 active businesses — not promises.'],
                ['q' => 'Who can become a shareholder?', 'a' => 'Up to ' . number_format($maxShareholders) . ' individuals may participate. Applications are reviewed for compliance; subscription may close when the pool is fully allocated.'],
            ] as $index => $faq)
                <div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                    <button type="button"
                            class="flex w-full items-center justify-between px-5 py-4 text-left text-sm font-semibold text-ink-900"
                            @click="open = open === {{ $index + 1 }} ? null : {{ $index + 1 }}">
                        {{ $faq['q'] }}
                        <svg class="h-5 w-5 shrink-0 text-slate-400 transition" :class="open === {{ $index + 1 }} ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="open === {{ $index + 1 }}" x-cloak x-transition class="border-t border-slate-100 px-5 py-4 text-sm leading-relaxed text-slate-600">
                        {{ $faq['a'] }}
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Application --}}
<section class="py-20 lg:py-24" id="apply">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-3xl bg-ink-950 shadow-lift">
            <div class="grid lg:grid-cols-2">
                <div class="p-8 sm:p-12 lg:p-14">
                    <p class="text-sm font-semibold uppercase tracking-widest text-violet-300">Secure application</p>
                    <h2 class="mt-3 text-3xl font-bold text-white">Start your shareholder onboarding</h2>
                    <p class="mt-4 text-slate-300">Submit your details and desired allocation. We will guide you through payment and dashboard access.</p>
                    <ul class="mt-8 space-y-3 text-sm text-slate-400">
                        <li class="flex items-center gap-2"><svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Encrypted, GDPR-conscious handling</li>
                        <li class="flex items-center gap-2"><svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Mobile money &amp; bank-ready checkout</li>
                        <li class="flex items-center gap-2"><svg class="h-5 w-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg> Dedicated shareholder portal</li>
                    </ul>
                </div>
                <div class="bg-white p-8 sm:p-12 lg:p-14">
                    @if(! $subscriptionOpen)
                        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-950">
                            <p class="font-semibold">Subscriptions are currently closed.</p>
                            <p class="mt-2">Contact our team to join the waitlist.</p>
                            <a href="{{ whatsapp_support_url('Shareholder program waitlist') }}" target="_blank" rel="noopener" class="mt-4 inline-flex font-semibold text-brand-700 hover:underline">WhatsApp support</a>
                        </div>
                    @else
                        <form method="POST" action="{{ route('shareholders.start') }}" class="space-y-4">
                            @csrf
                            @if($errors->any())
                                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                                    <ul class="list-inside list-disc space-y-1">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Full name</label>
                                <input type="text" name="name" value="{{ old('name') }}" required
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-500">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Email</label>
                                <input type="email" name="email" value="{{ old('email') }}" required
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-500">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Phone (WhatsApp)</label>
                                <input type="tel" name="phone" value="{{ old('phone') }}" required
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-500">
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium text-slate-700">Shares to acquire</label>
                                <input type="number" name="shares" min="1" max="{{ max(1, min(100, (int) floor($remainingShares))) }}" value="{{ old('shares', 1) }}" required
                                       class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm focus:border-violet-500 focus:ring-violet-500">
                                <p class="mt-1 text-xs text-slate-500">UGX {{ number_format($pricePerShare, 0) }} per share · {{ number_format($remainingShares, 2) }} available</p>
                            </div>
                            <button type="submit" class="w-full rounded-xl bg-ink-900 py-3.5 text-sm font-bold text-white transition hover:bg-ink-800">
                                Continue to full application
                            </button>
                            <p class="text-center text-xs text-slate-500">Next step: create your login and complete payment on the shareholder portal.</p>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="border-t border-slate-200 bg-white py-10">
    <div class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-4 px-4 text-center sm:flex-row sm:text-left sm:px-6 lg:px-8">
        <p class="text-sm text-slate-500">&copy; {{ date('Y') }} {{ $brand }}. Shareholder program terms apply.</p>
        <div class="flex flex-wrap justify-center gap-4 text-sm font-medium text-slate-600">
            <a href="{{ route('home') }}" class="hover:text-ink-900">Merchant home</a>
            <a href="{{ route('shareholder.login') }}" class="hover:text-ink-900">Shareholder login</a>
        </div>
    </div>
</footer>
@endsection

@push('scripts')
<script>
function shareholderCalculator(config) {
    return {
        shares: 1,
        maxShares: config.maxShares || 1,
        pricePerShare: config.pricePerShare,
        perBusinessPerShare: config.perBusinessPerShare,
        capMultiplier: config.capMultiplier,
        scenarios: config.scenarios || [1000, 5000, 10000],
        formatNumber(value) {
            return Number(value || 0).toLocaleString('en-UG');
        },
        formatUgx(value) {
            return 'UGX ' + this.formatNumber(Math.round(Number(value || 0)));
        },
        investmentTotal() {
            return this.shares * this.pricePerShare;
        },
        monthlyAt(businesses) {
            return this.shares * Number(businesses) * this.perBusinessPerShare;
        },
        capTotal() {
            return this.shares * this.pricePerShare * this.capMultiplier;
        },
    };
}
</script>
@endpush
