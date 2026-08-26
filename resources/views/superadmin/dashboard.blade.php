@extends('layouts.superadmin')

@section('title', 'Tenant Overview')

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-bold tracking-tight">Tenant Overview</h1>
    <p class="mt-1 text-sm text-gray-500">All registered businesses across the platform</p>
</div>

<div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Businesses</p>
        <p class="mt-2 text-3xl font-bold">{{ $stats['businesses'] }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Active</p>
        <p class="mt-2 text-3xl font-bold text-emerald-400">{{ $stats['active_subscriptions'] }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Expired / Inactive</p>
        <p class="mt-2 text-3xl font-bold text-red-400">{{ $stats['expired_or_inactive'] }}</p>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5">
        <p class="text-xs font-medium uppercase tracking-wide text-gray-500">Tenant Users</p>
        <p class="mt-2 text-3xl font-bold">{{ $stats['users'] }}</p>
    </div>
</div>

<div class="overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="border-b border-gray-200 px-6 py-4">
        <h2 class="font-semibold">All Businesses</h2>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-gray-200 text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-6 py-3">Business</th>
                    <th class="px-6 py-3">Login URL</th>
                    <th class="px-6 py-3">Email</th>
                    <th class="px-6 py-3">Users</th>
                    <th class="px-6 py-3">Status</th>
                    <th class="px-6 py-3">Plan</th>
                    <th class="px-6 py-3">Trial Ends</th>
                    <th class="px-6 py-3">Registered</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($businesses as $business)
                    <tr class="hover:bg-gray-100/50">
                        <td class="px-6 py-4 font-medium">{{ $business->name }}</td>
                        <td class="px-6 py-4">
                            @if($business->portal_slug)
                                <a href="{{ $business->portalLoginUrl() }}" target="_blank" rel="noopener"
                                   class="font-medium text-violet-600 hover:text-violet-800">Open portal</a>
                                <p class="mt-1 max-w-xs break-all text-xs text-gray-500">{{ $business->portalLoginUrl() }}</p>
                            @else
                                <span class="text-xs text-gray-400">Not set</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ $business->email }}</td>
                        <td class="px-6 py-4">{{ $business->users_count }}</td>
                        <td class="px-6 py-4">
                            @if($business->isSubscriptionExpired())
                                <span class="inline-flex rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Expired</span>
                            @else
                                <span class="inline-flex rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Active</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 capitalize text-gray-500">{{ $business->subscription_status }}</td>
                        <td class="px-6 py-4 text-gray-500">
                            {{ $business->trial_ends_at ? $business->trial_ends_at->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-6 py-4 text-gray-500">{{ $business->created_at->format('M d, Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-8 text-center text-gray-500">No businesses registered yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($businesses->hasPages())
        <div class="border-t border-gray-200 px-6 py-4">{{ $businesses->links() }}</div>
    @endif
</div>

@if($stats['recent_activity']->isNotEmpty())
<div class="mt-8 overflow-hidden rounded-xl border border-gray-200 bg-white">
    <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
        <h2 class="font-semibold">Recent Platform Activity</h2>
        <a href="{{ route('superadmin.activity') }}" class="text-xs text-violet-400 hover:text-violet-700">View all →</a>
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
