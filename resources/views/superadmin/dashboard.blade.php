@extends('layouts.superadmin')

@section('title', 'Master Dashboard')

@section('content')
<div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
    <div>
        <h1 class="text-2xl font-bold tracking-tight">Master Dashboard</h1>
        <p class="mt-1 text-sm text-gray-500">Global visibility across all businesses and platform data</p>
        @if(auth()->user()->isSubAdmin())
            <p class="mt-2 inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-800">SubAdmin — view &amp; create only</p>
        @endif
    </div>
    <form method="GET" action="{{ route('superadmin.search') }}" class="w-full max-w-xl">
        <div class="flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Global search businesses, users, products…"
                   class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:border-violet-500 focus:outline-none">
            <button type="submit" class="shrink-0 rounded-lg bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-500">Search</button>
        </div>
    </form>
</div>

<div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4 xl:grid-cols-6">
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Businesses</p>
        <p class="mt-2 text-2xl font-bold">{{ number_format($stats['businesses']) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Tenant Users</p>
        <p class="mt-2 text-2xl font-bold">{{ number_format($stats['users']) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Products</p>
        <p class="mt-2 text-2xl font-bold">{{ number_format($stats['products']) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Sales</p>
        <p class="mt-2 text-2xl font-bold text-emerald-600">{{ number_format($stats['sales']) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Expenses</p>
        <p class="mt-2 text-2xl font-bold text-amber-600">{{ number_format($stats['expenses']) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Active Subs</p>
        <p class="mt-2 text-2xl font-bold text-violet-600">{{ number_format($stats['active_subscriptions']) }}</p>
    </div>
</div>

<div class="mb-8 grid gap-4 lg:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Platform sales volume</p>
        <p class="mt-2 text-3xl font-bold">UGX {{ number_format($stats['sales_volume'], 0) }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Platform expense volume</p>
        <p class="mt-2 text-3xl font-bold text-red-600">UGX {{ number_format($stats['expense_volume'], 0) }}</p>
    </div>
</div>

<div class="mb-8">
    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-gray-500">Global entity management</h2>
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach(\App\Support\SuperAdmin\EntityRegistry::all() as $key => $entity)
            <a href="{{ route('superadmin.entities.index', $key) }}"
               class="rounded-xl border border-gray-200 bg-white p-4 text-center transition hover:border-violet-300 hover:shadow-sm">
                <p class="text-sm font-semibold text-gray-900">{{ $entity['label'] }}</p>
                <p class="mt-1 text-xs text-violet-600">Manage →</p>
            </a>
        @endforeach
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="font-semibold">Recent Businesses</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">Business</th>
                    <th class="px-6 py-3">Users</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($businesses as $business)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 font-medium">{{ $business->name }}</td>
                        <td class="px-6 py-4">{{ $business->users_count }}</td>
                        <td class="px-6 py-4 capitalize">{{ $business->subscription_status }}</td>
                        <td class="px-6 py-4 text-right whitespace-nowrap">
                            @can('platform-full-access')
                                <form method="POST" action="{{ route('superadmin.impersonate.start', $business->id) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="text-violet-600 hover:text-violet-800">Impersonate</button>
                                </form>
                            @endcan
                            <a href="{{ route('superadmin.entities.show', ['businesses', $business->id]) }}" class="ml-3 text-gray-600 hover:text-gray-900">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-gray-500">No businesses yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($stats['recent_activity']->isNotEmpty())
<div class="mt-8 overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
        <h2 class="font-semibold">Recent Platform Activity</h2>
        <a href="{{ route('superadmin.activity') }}" class="text-xs text-violet-600 hover:text-violet-800">View all →</a>
    </div>
    <ul class="divide-y divide-gray-200">
        @foreach($stats['recent_activity'] as $log)
            <li class="flex items-start justify-between gap-4 px-6 py-3 text-sm">
                <div>
                    <p class="font-medium">{{ $log->summary }}</p>
                    <p class="mt-0.5 text-xs text-gray-500">{{ $log->action }} · {{ optional($log->business)->name ?? 'Platform' }}</p>
                </div>
                <time class="shrink-0 text-xs text-gray-500">{{ $log->created_at->diffForHumans() }}</time>
            </li>
        @endforeach
    </ul>
</div>
@endif
@endsection
