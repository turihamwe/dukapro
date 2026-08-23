@extends('layouts.superadmin')

@section('title', 'Tenant Overview')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight">Tenant Overview</h1>
    <p class="mt-1 text-sm text-slate-400">All registered businesses across the platform</p>
</div>

<div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Businesses</p>
        <p class="mt-2 text-3xl font-bold">{{ $stats['businesses'] }}</p>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Active</p>
        <p class="mt-2 text-3xl font-bold text-emerald-400">{{ $stats['active_subscriptions'] }}</p>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Expired / Inactive</p>
        <p class="mt-2 text-3xl font-bold text-red-400">{{ $stats['expired_or_inactive'] }}</p>
    </div>
    <div class="rounded-xl border border-slate-800 bg-slate-900 p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Tenant Users</p>
        <p class="mt-2 text-3xl font-bold">{{ $stats['users'] }}</p>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
    <div class="border-b border-slate-800 px-6 py-4">
        <h2 class="font-semibold">All Businesses</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-800 text-xs uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-6 py-3">Business</th>
                    <th class="px-6 py-3">Email</th>
                    <th class="px-6 py-3">Users</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Plan</th>
                    <th class="px-6 py-3">Trial Ends</th>
                    <th class="px-6 py-3">Registered</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800">
                @forelse($businesses as $business)
                    <tr class="hover:bg-slate-800/50">
                        <td class="px-6 py-4 font-medium">{{ $business->name }}</td>
                        <td class="px-6 py-4 text-slate-400">{{ $business->email }}</td>
                        <td class="px-6 py-4">{{ $business->users_count }}</td>
                        <td class="px-6 py-4">
                            @if($business->isSubscriptionExpired())
                                <span class="inline-flex rounded-md bg-red-950 px-2 py-0.5 text-xs font-medium text-red-300">Expired</span>
                            @else
                                <span class="inline-flex rounded-md bg-emerald-950 px-2 py-0.5 text-xs font-medium text-emerald-300">Active</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 capitalize text-slate-400">{{ $business->subscription_status }}</td>
                        <td class="px-6 py-4 text-slate-400">
                            {{ $business->trial_ends_at ? $business->trial_ends_at->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-6 py-4 text-slate-400">{{ $business->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-slate-500">No businesses registered yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($businesses->hasPages())
        <div class="border-t border-slate-800 px-6 py-4">{{ $businesses->links() }}</div>
    @endif
</div>

@if($stats['recent_activity']->isNotEmpty())
<div class="mt-8 overflow-hidden rounded-xl border border-slate-800 bg-slate-900">
    <div class="flex items-center justify-between border-b border-slate-800 px-6 py-4">
        <h2 class="font-semibold">Recent Platform Activity</h2>
        <a href="{{ route('superadmin.activity') }}" class="text-xs text-violet-400 hover:text-violet-300">View all →</a>
    </div>
    <ul class="divide-y divide-slate-800">
        @foreach($stats['recent_activity'] as $log)
            <li class="flex items-start justify-between gap-4 px-6 py-3 text-sm">
                <div>
                    <p class="font-medium">{{ $log->summary }}</p>
                    <p class="mt-0.5 text-xs text-slate-500">{{ $log->action }} · {{ optional($log->business)->name ?? 'Platform' }}</p>
                </div>
                <time class="shrink-0 text-xs text-slate-500">{{ $log->created_at->diffForHumans() }}</time>
            </li>
        @endforeach
    </ul>
</div>
@endif
@endsection
