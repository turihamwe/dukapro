@extends('layouts.superadmin')

@section('title', 'Platform Business Overview')

@section('content')
<div class="space-y-10" x-data="{ focusHorizon: '{{ $platform['focus_horizon'] ?? 'monthly' }}' }">
    {{-- Business Overview --}}
    <section id="platform-overview">
        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight">Platform Business Overview</h1>
            <p class="mt-1 text-sm text-gray-500">Aggregate sales performance across all DukaPro tenant businesses</p>
        </div>

        {{-- Executive summary --}}
        <div class="mb-6 overflow-hidden rounded-2xl border border-slate-800 bg-gradient-to-br from-slate-900 via-slate-900 to-emerald-950 p-6 text-white shadow-lg sm:p-8">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-300">Executive growth summary</p>
                    <p class="mt-3 text-base leading-relaxed text-slate-100 sm:text-lg">{{ $platform['executive_summary'] }}</p>
                </div>
                <div class="grid shrink-0 grid-cols-2 gap-3 sm:grid-cols-1 lg:w-48">
                    <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                        <p class="text-[10px] uppercase tracking-wide text-slate-400">Active businesses</p>
                        <p class="mt-1 text-xl font-bold">{{ number_format($platform['active_businesses']) }}</p>
                    </div>
                    <div class="rounded-lg border border-white/10 bg-white/5 px-4 py-3">
                        <p class="text-[10px] uppercase tracking-wide text-slate-400">Trading this month</p>
                        <p class="mt-1 text-xl font-bold text-emerald-300">{{ number_format($platform['businesses_with_sales']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Horizon metric cards --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach($platform['horizons'] as $key => $horizon)
                @include('platform.partials.horizon-metric-card', ['key' => $key, 'horizon' => $horizon])
            @endforeach
        </div>

        {{-- Comparative table --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-6 py-4">
                <h2 class="font-semibold text-gray-900">Multi-period comparison</h2>
                <p class="mt-1 text-xs text-gray-500">Platform-wide completed sales across daily, weekly, monthly, and annual horizons</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                        <tr>
                            <th class="px-4 py-3">Period</th>
                            <th class="px-4 py-3 text-right">Transactions</th>
                            <th class="px-4 py-3 text-right">Revenue (UGX)</th>
                            <th class="px-4 py-3 text-right">Gross profit (UGX)</th>
                            <th class="px-4 py-3 text-right">Margin</th>
                            <th class="px-4 py-3 text-right">Revenue growth</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($platform['horizons'] as $key => $horizon)
                            @php
                                $revGrowth = $horizon['growth']['revenue'] ?? null;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $horizon['label'] }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($horizon['transaction_count']) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-violet-700 tabular-nums">{{ number_format($horizon['revenue'], 0) }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-emerald-700 tabular-nums">{{ number_format($horizon['gross_profit'], 0) }}</td>
                                <td class="px-4 py-3 text-right tabular-nums">{{ number_format($horizon['gross_margin'], 1) }}%</td>
                                <td class="px-4 py-3 text-right tabular-nums">
                                    @if($revGrowth === null)
                                        <span class="text-gray-400">—</span>
                                    @else
                                        <span class="{{ $revGrowth >= 0 ? 'text-emerald-600' : 'text-rose-600' }} font-medium">
                                            {{ $revGrowth >= 0 ? '+' : '' }}{{ number_format($revGrowth, 1) }}%
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- Affiliate Performance (below business overview) --}}
    @include('affiliates.partials.command-center')
</div>
@endsection
